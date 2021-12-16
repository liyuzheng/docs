<?php


namespace App\Console\Commands;

use App\Models\Version;
use Illuminate\Console\Command;

class VersionPublishCommand extends Command
{
    protected $signature = 'xiaoquan:version_publish {action} {args*}';
    protected $description = '版本发布脚本集合';

    public function handle()
    {
        $action = $this->argument('action');
        switch ($action) {
            case 'insert_db_version':
                $args = $this->argument('args');
                if (!is_array($args) || count($args) != 2) {
                    $this->error('请输入正确的参数, 格式: xiaoquan:version_publish
                        version_db_insert version appname');
                }

                [$version, $appname] = $args;
                $createData = [
                    'appname'    => $appname,
                    'os'         => Version::OS_IOS,
                    'version'    => $version,
                    'bundle_id'  => $appname,
                    'notice'     => sprintf('自动发布ios%s版本', $version),
                    'audited_at' => time(),
                ];
                rep()->version->getQuery()->create($createData);
                break;
            default:
                $this->error('不合法的 action');
        }
    }
}
