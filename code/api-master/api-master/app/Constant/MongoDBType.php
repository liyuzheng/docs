<?php
/**
 * Created by PhpStorm.
 * User: ailuoy
 * Date: 2019/4/15
 * Time: 下午11:07
 */

namespace App\Constant;


class MongoDBType
{
    const UPLOAD_FILE_RECORD_TYPE_USER_AVATAR  = 'user_avatar';
    const UPLOAD_FILE_RECORD_TYPE_USER_PHOTO   = 'user_photo';
    const UPLOAD_FILE_RECORD_TYPE_WATERMARK    = 'watermark';
    const UPLOAD_FILE_RECORD_TYPE_QRCODE       = 'qrcode';
    const UPLOAD_FILE_RECORD_TYPE_REPORT       = 'report';
    const UPLOAD_FILE_RECORD_TYPE_FEEDBACK     = 'feedback';
    const UPLOAD_FILE_RECORD_TYPE_ERROR_REPORT = 'error_report';
    const UPLOAD_FILE_RECORD_TYPE_USER_VIDEO   = 'user_video';
    const UPLOAD_FILE_RECORD_TYPE_MOMENT       = 'moment';
    const UPLOAD_FILE_RECORD_TYPE_BANNER       = 'banner';
    const UPLOAD_FILE_RECORD_TYPE_FACE_AUTH    = 'face_auth';
}
