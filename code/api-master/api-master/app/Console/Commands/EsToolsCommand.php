<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class EsToolsCommand extends Command
{
    protected $signature   = 'xiaoquan:es_tools';
    protected $description = 'es搜索';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $action = $this->choice('请选择动作类型', [
            'init_user_location'             => '创建user坐标mapping',
            'update_user_location_mapping'   => '更新user坐标mapping',
            'sync_import_user_location_data' => '同步导入数据',
            'delete_user_location_index'     => '删除user坐标mapping',
            'search_user'                    => '搜索用户',
            'feed_users'                     => '测试feed搜索',
            'init_chat_im_index'             => '创建chat_im搜索mapping',
            'init_moment'                    => '创建moment动态mapping',
            'update_moment_mapping'          => '更新moment动态mapping',
            'delete_chat_im_index'           => '删除chat_im搜索mapping',
            'search_chat'                    => '搜索聊天记录',
        ]);
        switch ($action) {
            case 'init_user_location':
                $this->info('create index');
                dd('上线后禁止使用');
                //                dd('上线后禁止使用');
                $response = pocket()->esUser->postIndex(true);
                d($response);
                $this->info('updateMapping');
                $response = pocket()->esUser->updateMapping();
                d($response);
                $this->info('updateSetting');
                $mappingResponse = pocket()->esUser->getMappings();
                d($mappingResponse);
                $settingsResponse = pocket()->esUser->getSettings();
                dd($settingsResponse);
                break;
            case 'update_user_location_mapping':
                $this->info('updateMapping');
                $response = pocket()->esUser->updateMapping();
                d($response);
                $mappingResponse = pocket()->esUser->getMappings();
                dd($mappingResponse);
                break;
            case 'delete_user_location_index':
                $response = pocket()->esUser->deleteIndex();
                dd($response);
                break;
            case 'init_chat_im_index':
                $this->info('create index');
                $response = pocket()->esImChat->postIndex(true);
                d($response);
                $this->info('updateMapping');
                $response = pocket()->esImChat->updateMapping();
                d($response);
                $this->info('updateSetting');
                $mappingResponse = pocket()->esImChat->getMappings();
                d($mappingResponse);
                $settingsResponse = pocket()->esImChat->getSettings();
                dd($settingsResponse);
                break;
            case 'delete_chat_im_index':
                $response = pocket()->esImChat->deleteIndex();
                dd($response);
                break;
            case 'sync_import_user_location_data':
                DB::table('user')->select('id')->orderBy('id')->chunk(3000, function ($users) {
                    $response = pocket()->esUser->batchUpdateOrPostUserLocationFromMongo($users->pluck('id')->toArray());
                    $this->info('1000条处理完毕！');
                });
                //                mongodb('user')->select('_id')->orderBy('_id')->chunk(1000, function ($users) {
                //                    $response = pocket()->esUser->batchUpdateOrPostUserLocationFromMongo($users->pluck('_id')->toArray());
                //                    $this->info('1000条处理完毕！');
                //                });
                $this->info('success！');

                break;
            case 'init_moment':
                $this->info('create index');
                $response = pocket()->esMoment->postIndex(true);
                d($response);
                $this->info('updateMapping');
                $response = pocket()->esMoment->updateMapping();
                d($response);
                $this->info('updateSetting');
                $mappingResponse = pocket()->esMoment->getMappings();
                d($mappingResponse);
                $settingsResponse = pocket()->esMoment->getSettings();
                dd($settingsResponse);
                break;
            case 'update_moment_mapping':
                $this->info('updateMapping');
                $response = pocket()->esMoment->updateMapping();
                d($response);
                $mappingResponse = pocket()->esMoment->getMappings();
                dd($mappingResponse);
                break;
            case 'search_user':
                $userId = $this->ask('请输入用户id', 0);
                //                $charmGril      = 1;
                //                $gender         = 2;
                //                $uploadLocation = 1;
                //                $response       = pocket()->esUser->getSearchLocationUsersIdByUserId(10, 10, 103.614592300824, 36.506823040579,
                //                    $charmGril, $gender, $uploadLocation,1608620217
                //                );
                //                dd($response);

                $res = pocket()->esUser->getUserByUserId($userId);
                dd($res);
                break;
            case 'feed_users':
                $page           = $this->ask('请输入page', 0);
                $charmGril      = 1;
                $gender         = 2;
                $uploadLocation = 1;
                //                $response       = pocket()->esUser->getSearchLocationUsersIdByUserId(0, 10, 116.47400390519216, 39.99661698233894,
                //                    $charmGril, $gender, $uploadLocation, false
                //                );
                $userIds = pocket()->esUser->getSearchLocationUsersIdByUserId($page * 10, 10, 116.47400390519216,
                    39.99661698233894,
                    $charmGril, $gender, $uploadLocation, false
                );
                dd($userIds);
                break;
            case 'search_chat':
                $userId     = $this->ask('请输入用户id', 0);
                $page       = $this->ask('请输入page', 1);
                $response   = pocket()->esImChat->searchImChat($userId, 0, 0, 0, "", [], 10, $page);
                $resultData = $response->getData();
                dd($resultData);
                break;
        }
    }
}
