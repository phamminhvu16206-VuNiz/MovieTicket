<?php

namespace Database\Seeders;

use App\Models\Movie;
use App\Models\Room;
use App\Models\Showtime;
use Illuminate\Database\Seeder;
use RuntimeException;

class AdditionalMoviesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $movies = [
            [
                'title' => 'Oppenheimer',
                'poster' => 'oppenheimer.svg',
                'genre' => 'Tiểu sử, Chính kịch',
                'director' => 'Christopher Nolan',
                'actors' => 'Cillian Murphy, Emily Blunt, Matt Damon',
                'duration' => 180,
                'release_days' => -60,
                'status' => 'showing',
                'description' => 'Câu chuyện về nhà vật lý J. Robert Oppenheimer và bước ngoặt làm thay đổi thế giới.',
            ],
            [
                'title' => 'Kung Fu Panda 4',
                'poster' => 'kung-fu-panda-4.svg',
                'genre' => 'Hoạt hình, Hài hước, Phiêu lưu',
                'director' => 'Mike Mitchell',
                'actors' => 'Jack Black, Awkwafina, Viola Davis',
                'duration' => 94,
                'release_days' => -30,
                'status' => 'showing',
                'description' => 'Po bước vào chuyến phiêu lưu mới để tìm người kế nhiệm và đối mặt với một mối đe dọa bí ẩn.',
            ],
            [
                'title' => 'Godzilla x Kong: Đế Chế Mới',
                'poster' => 'godzilla-x-kong.svg',
                'genre' => 'Hành động, Khoa học viễn tưởng',
                'director' => 'Adam Wingard',
                'actors' => 'Rebecca Hall, Brian Tyree Henry, Dan Stevens',
                'duration' => 115,
                'release_days' => -20,
                'status' => 'showing',
                'description' => 'Godzilla và Kong cùng khám phá bí mật sâu trong lòng Trái Đất và đối đầu một thế lực mới.',
            ],
            [
                'title' => 'Wonka',
                'poster' => 'wonka.svg',
                'genre' => 'Gia đình, Phiêu lưu, Nhạc kịch',
                'director' => 'Paul King',
                'actors' => 'Timothée Chalamet, Calah Lane, Hugh Grant',
                'duration' => 116,
                'release_days' => 7,
                'status' => 'upcoming',
                'description' => 'Hành trình đầy màu sắc của chàng thợ làm chocolate trẻ tuổi trước khi mở cửa tiệm kỳ diệu.',
            ],
            [
                'title' => 'Doraemon: Nobita Và Bản Giao Hưởng Địa Cầu',
                'poster' => 'doraemon-symphony.svg',
                'genre' => 'Hoạt hình, Gia đình, Phiêu lưu',
                'director' => 'Kazuaki Imai',
                'actors' => 'Wasabi Mizuta, Megumi Ohara, Yumi Kakazu',
                'duration' => 115,
                'release_days' => 14,
                'status' => 'upcoming',
                'description' => 'Nobita và những người bạn bước vào cuộc phiêu lưu âm nhạc để bảo vệ giai điệu của Trái Đất.',
            ],
        ];

        $activeRooms = Room::query()
            ->where('status', 'active')
            ->orderBy('id')
            ->get();

        if ($activeRooms->isEmpty()) {
            throw new RuntimeException('Không thể tạo suất chiếu mẫu vì chưa có phòng chiếu đang hoạt động.');
        }

        foreach ($movies as $index => $movieData) {
            $movie = Movie::updateOrCreate(
                ['title' => $movieData['title']],
                [
                    'poster' => $movieData['poster'],
                    'description' => $movieData['description'],
                    'genre' => $movieData['genre'],
                    'director' => $movieData['director'],
                    'actors' => $movieData['actors'],
                    'duration' => $movieData['duration'],
                    'release_date' => today()->addDays($movieData['release_days'])->toDateString(),
                    'trailer' => 'https://www.youtube.com/results?search_query='.urlencode($movieData['title'].' trailer'),
                    'status' => $movieData['status'],
                ]
            );

            $this->createShowtimes($movie, $activeRooms->all(), $index + 1);
        }
    }

    /**
     * @param  array<int, Room>  $rooms
     */
    private function createShowtimes(Movie $movie, array $rooms, int $startingDay): void
    {
        $showtimeCount = $movie->showtimes()
            ->where('status', 'active')
            ->where('start_time', '>=', now())
            ->count();

        for ($dayOffset = $startingDay; $showtimeCount < 2 && $dayOffset <= $startingDay + 30; $dayOffset++) {
            foreach ([12, 18] as $hour) {
                if ($showtimeCount >= 2) {
                    break;
                }

                $startTime = today()->addDays($dayOffset)->setHour($hour)->setMinute(0)->setSecond(0);
                $endTime = $startTime->copy()->addMinutes($movie->duration + 10);

                foreach ($rooms as $room) {
                    $isRoomOccupied = Showtime::query()
                        ->where('room_id', $room->id)
                        ->where('status', 'active')
                        ->where('start_time', '<', $endTime)
                        ->where('end_time', '>', $startTime)
                        ->exists();

                    if ($isRoomOccupied) {
                        continue;
                    }

                    $movie->showtimes()->create([
                        'room_id' => $room->id,
                        'start_time' => $startTime,
                        'end_time' => $endTime,
                        'price' => $hour === 12 ? 85000 : 95000,
                        'status' => 'active',
                    ]);
                    $showtimeCount++;

                    break;
                }
            }
        }

        if ($showtimeCount < 2) {
            throw new RuntimeException("Không tìm được đủ suất chiếu trống cho phim {$movie->title} trong 30 ngày tới.");
        }
    }
}
