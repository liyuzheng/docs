<?php


namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Intervention\Image\Facades\Image;
use App\Http\Requests\Uploads\UploadsSingleRequest;
use App\Foundation\Services\Guzzle\GuzzleHandle;
use App\Constant\ApiBusinessCode;
use App\Constant\MongoDBType;
use App\Http\Requests\Uploads\QrcodePosterRequest;
use Endroid\QrCode\QrCode;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Http\JsonResponse;
use App\Http\Requests\Uploads\RemoteSingleRequest;
use Exception;

/**
 * 公共上传资源的地址
 * Class UploadsController
 * @package App\Http\Controllers
 */
class UploadsController extends BaseController
{
    /**
     * 上传单张文件
     *
     * @param  UploadsSingleRequest  $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function single(UploadsSingleRequest $request)
    {
        $type      = request('type');
        $uuid      = request('uuid');
        $watermark = (bool)request('watermark', 0);
        $file      = request()->file('file');
        $extension = parse_mine_type_to_ext($file->getClientMimeType());
        $fileName  = get_md5_random_str() . '.' . $extension;
        switch ($type) {
            case 'user_avatar':
                $filePath       = config('custom.upload_path.user.avatar.db_path') . $fileName;
                $msg            = trans('messages.create_avatar');
                $fileRecordType = MongoDBType::UPLOAD_FILE_RECORD_TYPE_USER_AVATAR;
                break;
            case 'user_photo':
                $filePath       = config('custom.upload_path.user.photo.db_path') . $fileName;
                $msg            = trans('messages.create_photo');
                $fileRecordType = MongoDBType::UPLOAD_FILE_RECORD_TYPE_USER_PHOTO;
                break;
            case 'watermark':
                $filePath       = config('custom.upload_path.common.watermark.db_path') . 'water.png';
                $msg            = trans('messages.create_watermark');
                $fileRecordType = MongoDBType::UPLOAD_FILE_RECORD_TYPE_WATERMARK;
                break;
            case 'qrcode':
                $filePath       = config('custom.upload_path.user.qrcode.db_path') . $fileName;
                $msg            = trans('messages.create_qrcode');
                $fileRecordType = MongoDBType::UPLOAD_FILE_RECORD_TYPE_QRCODE;
                break;
            case 'report':
                $filePath       = config('custom.upload_path.user.report.db_path') . $fileName;
                $msg            = trans('messages.create_report_picture');
                $fileRecordType = MongoDBType::UPLOAD_FILE_RECORD_TYPE_REPORT;
                break;
            case 'feedback':
                $filePath       = config('custom.upload_path.user.feedback.db_path') . $fileName;
                $msg            = trans('messages.create_feedback_picture');
                $fileRecordType = MongoDBType::UPLOAD_FILE_RECORD_TYPE_FEEDBACK;
                break;
            case 'error_report':
                $fileName       = $file->getClientOriginalName();
                $filePath       = config('custom.upload_path.common.error_report.db_path') . $fileName;
                $msg            = trans('messages.create_error_report');
                $fileRecordType = MongoDBType::UPLOAD_FILE_RECORD_TYPE_ERROR_REPORT;
                break;
            case 'user_video':
                $filePath       = config('custom.upload_path.user.user_video.db_path') . $fileName;
                $msg            = trans('messages.create_video');
                $fileRecordType = MongoDBType::UPLOAD_FILE_RECORD_TYPE_USER_VIDEO;
                break;
            case 'moment':
                $filePath       = config('custom.upload_path.moment.images.db_path') . $fileName;
                $msg            = trans('messages.create_moment_picture');
                $fileRecordType = MongoDBType::UPLOAD_FILE_RECORD_TYPE_MOMENT;
                break;
            case 'banner':
                $filePath       = config('custom.upload_path.banner.images.db_path') . $fileName;
                $msg            = trans('messages.create_moment_picture');
                $fileRecordType = MongoDBType::UPLOAD_FILE_RECORD_TYPE_BANNER;
                break;
            case 'face_auth':
                $filePath       = config('custom.upload_path.user.face_auth.db_path') . get_md5_random_str() . '.png';
                $msg            = trans('messages.create_face_auth_picture');
                $fileRecordType = MongoDBType::UPLOAD_FILE_RECORD_TYPE_FACE_AUTH;
                break;
            default:
                return api_rr()->requestParameterError();
                break;
        }
        $realPath = $file->getRealPath();
        $imgInfo  = getimagesize($realPath);
        $width    = $imgInfo[0] ?? 338;
        $height   = $imgInfo[1] ?? 565;
        $y        = intval(($height / 2) * 0.7);
        if ($watermark && in_array($type, ['user_avatar', 'user_photo'])) {
            $tmpImg = Image::make($realPath);
            try {
                $water  = Image::make(storage_path(config('custom.common_image_path.watermark.path')))->resize(intval($width / 4), intval($width / 12));
            } catch (Exception $e) {
                $log_data = ['width' => $width, 'error' => $e->getMessage()];
                return api_rr()->forbidCommon('图片尺寸过小,请更换后尝试');
            }
            $tmpImg->insert($water, 'bottom-right', 0, $y);
            storage()->put($filePath, $tmpImg->save($realPath));
        } else {
            storage()->put($filePath, file_get_contents($realPath));
        }

        $checked = false;
        if ($type === 'user_photo' && $uuid) {
            $client     = (new GuzzleHandle)->getClient();
            $checkedUrl = config('custom.user_photo.check_url') . '/v1/accounts/photo_compare';
            try {
                $response = $client->post($checkedUrl, [
                    'json' => [
                        'uuid'  => $uuid,
                        'photo' => $filePath,
                    ]
                ]);
                if ($response->getStatusCode() !== 200) {
                    $checked = false;
                }
                $result = json_decode($response->getBody()->getContents(), true);
                if ($result['code'] === ApiBusinessCode::SUCCESS) {
                    $checked = true;
                }
            } catch (\Exception $exception) {
                $checked = false;
            }
        }
        pocket()->common->commonQueueMoreByPocketJob(
            pocket()->mongodb,
            'postFileUploadRecordToMongo',
            [$filePath, $uuid, $fileRecordType, $checked]
        );

        return api_rr()->postOK([
            'preview'  => file_url($filePath),
            'resource' => $filePath,
            'checked'  => $checked
        ], $msg);
    }

    /**
     * 获取多个文件详细信息
     *
     * @param  Request  $request
     */
    public function fileInfo(Request $request)
    {
        $paths = $request->get('paths');
        if (!$paths) {
            return api_rr()->requestParameterMissing();
        }
        $fileNames = explode(',', $paths);
        $data      = [];
        foreach ($fileNames as $fileName) {
            $data[$fileName] = get_file_info($fileName);
        }

        return api_rr()->getOK($data);
    }

    /**
     * 生成二维码海报
     *
     * @param  QrcodePosterRequest  $request
     *
     * @return mixed
     */
    public function qrcodePoster(QrcodePosterRequest $request)
    {
        $code = request('code');
        $link = request('link');

        $backPath   = config('custom.common_image_path.poster_background.path');
        $background = Image::make(public_path($backPath));
        $height     = $background->getHeight();
        $width      = $background->getWidth();
        $background->text($code, $width / 2, $height / 2.3, function ($font) {
            $font->file(public_path('fonts/字悦班马宋刻本.ttf'));
            $font->size(150);
            $font->color('#FF9E0E');
            $font->align('center');
            $font->valign('top');
        });
        $filePath   = config('custom.upload_path.user.qrcode_poster.db_path') . get_md5_random_str() . '.png';
        $userQrcode = config('custom.upload_path.user.user_qrcode.db_path') . get_md5_random_str() . '.png';

        $qrCode = new QrCode($link);
        $qrCode->setSize(450);
        $qrCode->writeFile(public_path($userQrcode));

        $qrcode = Image::make(public_path($userQrcode));
        $background->insert($qrcode, 'bottom-center', $width / 2, $height / 5);

        $background->save($filePath);

        return api_rr()->postOK([
            'preview'  => file_url($filePath),
            'resource' => $filePath,
            'width'    => $width,
            'height'   => $height
        ], '合成成功！');
    }

    /**
     * 上传远程单张文件
     *
     * @param  RemoteSingleRequest  $request
     *
     * @return JsonResponse
     */
    public function remoteSingle(RemoteSingleRequest $request): JsonResponse
    {
        $url = request('url');
        $ext = request('ext');
        try {
            $client = new Client();
            $data   = $client->request('get', $url)->getBody()->getContents();
        } catch (GuzzleException $e) {
            return api_rr()->serviceUnknownForbid($e->getMessage());
        }
        $fileName = get_md5_random_str() . '.' . $ext;
        $filePath = config('custom.upload_path.common.chat_image.db_path') . $fileName;
        storage()->put($filePath, $data);

        return api_rr()->postOK([
            'preview'  => file_url($filePath),
            'resource' => $filePath
        ], trans('messages.update_success'));
    }
}
