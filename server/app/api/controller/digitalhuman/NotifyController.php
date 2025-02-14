<?php
// +----------------------------------------------------------------------
// | 贵州猿创科技 [致力于通过产品和服务，帮助创业者高效化开拓市场]
// +----------------------------------------------------------------------
// | Copyright(c)2019~2024 https://xhadmin.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed 这不是一个自由软件，不允许对程序代码以任何形式任何目的的再发行
// +----------------------------------------------------------------------
// | Author:贵州猿创科技<416716328@qq.com>|<Tel:18786709420>
// +----------------------------------------------------------------------
namespace app\api\controller\digitalhuman;

use app\api\controller\BaseApiController;
use app\common\model\digitalhuman\DhScene;
use app\common\model\digitalhuman\DhVideo;
use think\facade\Log;

class NotifyController extends BaseApiController
{
    public array $notNeedLogin = ['ydScene', 'ydVideo', 'gjVideo', 'gjScene'];

    public function ydScene()
    {
        $data = $this->getRequestData();
        Log::info('ydScene: ' . json_encode($data));

        $sceneInfo = DhScene::where('task_id', $data['data']['scene_task_id'])->find();
        if (!$sceneInfo) {
            Log::error('未找到场景信息: ' . $data['data']['scene_task_id']);
            return;
        }

        $coverImageUrl = $this->saveCoverImage(
            $data['data']['coverUrl'],
            'scene',
            $data['data']['scene_task_id']
        );

        $sceneInfo->status = ($data['code'] == 200) ? 2 : 3;
        if ($data['code'] == 200) {
            $sceneInfo->api_video_url = $data['data']['videoUrl'];
            $sceneInfo->cover_image = $coverImageUrl;
            $sceneInfo->scene_id = $data['data']['sceneId'];
        }

        $sceneInfo->save();
    }

    public function ydVideo()
    {
        $data = $this->getRequestData();
        Log::info('ydVideo: ' . json_encode($data));
        if (!isset($data['data']['video_task_id'])) {
            Log::error('未找到video_task_id');
            return $this->fail('未找到video_task_id');
        }
        $video = DhVideo::where('task_id', $data['data']['video_task_id'])->find();
        if (!$video) {
            Log::error('未找到创作记录: ' . $data['data']['video_task_id']);
            return $this->fail('未找到创作记录');
        }


        if ($data['code'] == 200) {
            $coverImageUrl = $this->saveCoverImage(
                $data['data']['coverUrl'],
                'video',
                $data['data']['video_task_id']
            );
            $video->status = 2;
            $video->video_url = $data['data']['videoUrl'];
            $video->duration = $data['data']['duration'];
            $video->cover_image = $coverImageUrl;
        } elseif ($data['code'] == 400) {
            $video->status = 3;
        }

        $video->save();
        return $this->success('');
    }

    private function getRequestData()
    {
        $data = $this->request->post();
        return is_string($data) ? json_decode($data, true) : $data;
    }


    private function saveCoverImage($coverUrl, $type, $taskId)
    {
        $coverDir = public_path("/uploads/digitalhuman/{$type}/cover");
        $coverImagePath = "{$coverDir}/{$taskId}.png";
        $coverImageUrl = "/uploads/digitalhuman/{$type}/cover/{$taskId}.png";

        if (!is_dir($coverDir)) {
            mkdir($coverDir, 0777, true);
        }

        $coverContent = file_get_contents($coverUrl);
        if ($coverContent !== false) {
            file_put_contents($coverImagePath, $coverContent);
        } else {
            Log::error("封面图错误: {$coverUrl}");
        }

        return $coverImageUrl;
    }


    public function gjScene()
    {
        $data = $this->request->post();
        if (is_string($data)) {
            $data = json_decode($data, true);
        }
        Log::info('gjScene: ' . json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        if ($data['taskType'] != 'video-training') {
            return;
        }
        $scene = DhScene::where('task_id', $data['data']['id'])->find();

        if ($data['data']['result'] == 'success') {
            $scene->status = '2';
            // $scene->api_video_url = $data['data']['coverUrl']['scene']['exampleUrl'];
            $scene->cover_image = $data['data']['coverUrl'];
            $scene->robotid = $data['data']['robotId'];
            $scene->scene_id = $data['data']['sceneId'];
        }
        if ($data['data']['result'] == 'fail') {
            $scene->status = '3';
            $scene->message = $data['data']['reason'];
        }
        $scene->save();
        return  $this->success('');
    }



    public function gjVideo()
    {
        $data = $this->request->post();

        if (is_string($data)) {
            $data = json_decode($data, true);
        }
        Log::info('gjVideo: ' . json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        if ($data['taskType'] != 'video-synthesis') {
            return $this->success();
        }

        $video = DhVideo::where('task_id', $data['data']['id'])->find();

        if (!$video) {
            Log::error('未找到创作记录: ' . $data['data']['id']);
            return $this->success();
        }

        if ($data['data']['result'] == 'success') {
            $video->status = '2';
            $video->api_video_url = $data['data']['videoUrl'];
            $video->cover_image = $data['data']['coverUrl'];
            $video->duration = $data['data']['duration'];
        } elseif ($data['code'] == 400) {
            $video->status = '3';
            $video->message = $data['data']['reason'];
        }
        $video->save();
    }
}
