<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Movie;
use App\Models\Booking;
use App\Models\BookingSeat;
use App\Models\Payment;
use App\Models\Review;
use App\Models\Combo;
use App\Models\Voucher;
use Illuminate\Support\Facades\DB;

class MovieController extends Controller
{
    // Dashboard Admin
    public function dashboard()
    {
        $totalMovies = Movie::count();

        // Tổng số vé/ghế đã bán
        $totalBookings = BookingSeat::whereHas('booking', function ($query) {
            $query->where('status', 'paid');
        })->count();

        // Tổng doanh thu
        $totalRevenue = Booking::where('status', 'paid')->sum('total_amount');
        $totalPayments = Payment::where('status','success')->count();
        $totalReviews = Review::where('status',true)->count();
        $totalComboSales = DB::table('booking_combos')->sum('quantity');
        $totalVoucherUsed = DB::table('booking_vouchers')->count();

        // 1. Dữ liệu Doanh thu 7 ngày gần nhất cho Biểu đồ cột
        $revenueByDays = Booking::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('SUM(total_amount) as total')
        )
            ->where('status', 'paid')
            ->where('created_at', '>=', now()->subDays(6)->startOfDay())
            ->groupBy('date')
            ->orderBy('date', 'ASC')
            ->pluck('total', 'date')
            ->toArray();

        // Mảng 7 ngày gần đây
        $chartDates = [];
        $chartRevenues = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $chartDates[] = date('d/m', strtotime($date));
            $chartRevenues[] = $revenueByDays[$date] ?? 0;
        }

        // 2. Dữ liệu Số vé bán ra theo Phim cho Biểu đồ tròn
        $movieTickets = Movie::select('movies.title', DB::raw('COUNT(booking_seats.id) as ticket_count'))
            ->join('showtimes', 'movies.id', '=', 'showtimes.movie_id')
            ->join('bookings', 'showtimes.id', '=', 'bookings.showtime_id')
            ->join('booking_seats', 'bookings.id', '=', 'booking_seats.booking_id')
            ->where('bookings.status', 'paid')
            ->groupBy('movies.id', 'movies.title')
            ->get();

        $movieLabels = $movieTickets->pluck('title');
        $movieCounts = $movieTickets->pluck('ticket_count');

        return view('admin.dashboard', compact(
            'totalMovies',
            'totalBookings',
            'totalRevenue',
            'chartDates',
            'chartRevenues',
            'movieLabels',
            'movieCounts',
            'totalPayments','totalReviews','totalComboSales','totalVoucherUsed'
        ));
    }

    // Danh sách phim
    public function index()
    {
        $movies = Movie::orderBy('created_at', 'desc')->paginate(10);
        return view('admin.movies.index', compact('movies'));
    }

    // Form thêm phim
    public function create()
    {
        return view('admin.movies.create');
    }

    // Lưu phim mới
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'genre' => 'required|string|max:255',
            'duration' => 'required|integer|min:1',
            'release_date' => 'required|date',
            'status' => 'required|in:showing,upcoming,stopped',
            'description' => 'nullable|string',
            'poster' => 'nullable|string|max:500',
            'director' => 'nullable|string|max:255',
            'actors' => 'nullable|string|max:1000',
            'trailer' => 'nullable|url|max:500',
        ]);

        Movie::create($request->only(['title','genre','duration','release_date','status','description','poster','director','actors','trailer']));

        return redirect()->route('admin.movies.index')->with('success', 'Thêm phim mới thành công!');
    }

    // Form sửa phim
    public function edit($id)
    {
        $movie = Movie::findOrFail($id);
        return view('admin.movies.edit', compact('movie'));
    }

    // Cập nhật phim
    public function update(Request $request, $id)
    {
        $movie = Movie::findOrFail($id);

        $request->validate([
            'title' => 'required|string|max:255',
            'genre' => 'required|string|max:255',
            'duration' => 'required|integer|min:1',
            'release_date' => 'required|date',
            'status' => 'required|in:showing,upcoming,stopped',
            'description' => 'nullable|string',
            'poster' => 'nullable|string|max:500',
            'director' => 'nullable|string|max:255',
            'actors' => 'nullable|string|max:1000',
            'trailer' => 'nullable|url|max:500',
        ]);

        $movie->update($request->only(['title','genre','duration','release_date','status','description','poster','director','actors','trailer']));

        return redirect()->route('admin.movies.index')->with('success', 'Cập nhật phim thành công!');
    }

    // Xóa phim
    public function destroy($id)
    {
        $movie = Movie::findOrFail($id);
        $movie->delete();

        return redirect()->route('admin.movies.index')->with('success', 'Đã xóa phim khỏi hệ thống!');
    }
}
