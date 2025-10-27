<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class VideoSeeder extends Seeder
{
    public function run()
    {
        $data = [
            [
                'video_id' => 'dQw4w9WgXcQ',
                'title' => 'Rick Astley - Never Gonna Give You Up',
                'description' => '官方音樂影片。Rick Astley - Never Gonna Give You Up (Official Video)',
                'duration' => 212,
                'thumbnail_url' => 'https://img.youtube.com/vi/dQw4w9WgXcQ/maxresdefault.jpg',
                'youtube_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
                'channel_id' => 'UC4JX40jDee_NI3rW7FzBHww',
                'channel_name' => 'Rick Astley',
                'published_at' => '2009-10-25 06:57:33',
            ],
            [
                'video_id' => 'e-IWRmpefzE',
                'title' => 'Billie Eilish - Bad Guy',
                'description' => 'Billie Eilish - Bad Guy (Official Music Video)',
                'duration' => 194,
                'thumbnail_url' => 'https://img.youtube.com/vi/e-IWRmpefzE/maxresdefault.jpg',
                'youtube_url' => 'https://www.youtube.com/watch?v=e-IWRmpefzE',
                'channel_id' => 'UCiGm_E4ZwYSHV3bcW1pnSeQ',
                'channel_name' => 'Billie Eilish',
                'published_at' => '2018-03-29 18:23:24',
            ],
            [
                'video_id' => '9bZkp7q19f0',
                'title' => 'PSY - GANGNAM STYLE(강남스타일) M/V',
                'description' => 'PSY - GANGNAM STYLE(강남스타일) M/V @ https://youtu.be/9bZkp7q19f0',
                'duration' => 253,
                'thumbnail_url' => 'https://img.youtube.com/vi/9bZkp7q19f0/maxresdefault.jpg',
                'youtube_url' => 'https://www.youtube.com/watch?v=9bZkp7q19f0',
                'channel_id' => 'UCrDkAvwXoNRdxfuJg_MqESA',
                'channel_name' => 'officialpsy',
                'published_at' => '2012-07-15 10:09:35',
            ],
            [
                'video_id' => 'kJQP7kiw9Fk',
                'title' => 'Luis Fonsi - Despacito ft. Daddy Yankee',
                'description' => 'Luis Fonsi - Despacito (Official Video) ft. Daddy Yankee',
                'duration' => 231,
                'thumbnail_url' => 'https://img.youtube.com/vi/kJQP7kiw9Fk/maxresdefault.jpg',
                'youtube_url' => 'https://www.youtube.com/watch?v=kJQP7kiw9Fk',
                'channel_id' => 'UCW5YeuPoDtjbYy_G09rLNrA',
                'channel_name' => 'Luis Fonsi',
                'published_at' => '2017-01-12 18:31:59',
            ],
            [
                'video_id' => 'jNQXAC9IVRw',
                'title' => 'Me at the zoo',
                'description' => 'The first video on YouTube. Me at the zoo',
                'duration' => 18,
                'thumbnail_url' => 'https://img.youtube.com/vi/jNQXAC9IVRw/maxresdefault.jpg',
                'youtube_url' => 'https://www.youtube.com/watch?v=jNQXAC9IVRw',
                'channel_id' => 'UC4QobU6nirQ1PJLo3GmstjQ',
                'channel_name' => 'jawed',
                'published_at' => '2005-04-23 18:27:15',
            ],
        ];

        // 使用批次插入以提高效率
        $videoModel = new \App\Models\VideoModel();
        foreach ($data as $video) {
            // 檢查是否已存在
            if (!$videoModel->findByYoutubeId($video['video_id'])) {
                $videoModel->insert($video);
            }
        }
    }
}
