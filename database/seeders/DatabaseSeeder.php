<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\Borrowing;
use App\Models\Category;
use App\Models\Notification;
use App\Models\Reservation;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ── Users ─────────────────────────────────────────────────────────────
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@library.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'phone' => '+1-555-0100',
            'is_active' => true,
        ]);

        $librarian = User::create([
            'name' => 'Sarah Johnson',
            'email' => 'librarian@library.com',
            'password' => Hash::make('password'),
            'role' => 'librarian',
            'phone' => '+1-555-0101',
            'department' => 'Library Services',
            'is_active' => true,
        ]);

        $students = [];
        $studentData = [
            ['name' => 'Alex Thompson', 'email' => 'alex@student.com', 'student_id' => 'STU001', 'department' => 'Computer Science'],
            ['name' => 'Priya Sharma', 'email' => 'priya@student.com', 'student_id' => 'STU002', 'department' => 'Mathematics'],
            ['name' => 'James Wilson', 'email' => 'james@student.com', 'student_id' => 'STU003', 'department' => 'Physics'],
            ['name' => 'Emma Davis', 'email' => 'emma@student.com', 'student_id' => 'STU004', 'department' => 'Literature'],
            ['name' => 'Raj Patel', 'email' => 'raj@student.com', 'student_id' => 'STU005', 'department' => 'Engineering'],
        ];

        foreach ($studentData as $data) {
            $students[] = User::create(array_merge($data, [
                'password' => Hash::make('password'),
                'role' => 'student',
                'is_active' => true,
            ]));
        }

        // ── Categories ────────────────────────────────────────────────────────
        $categories = [
            ['name' => 'Computer Science', 'slug' => 'computer-science', 'color' => '#6366f1', 'icon' => 'FiMonitor', 'description' => 'Programming, algorithms, and software engineering'],
            ['name' => 'Mathematics', 'slug' => 'mathematics', 'color' => '#8b5cf6', 'icon' => 'FiHash', 'description' => 'Pure and applied mathematics'],
            ['name' => 'Physics', 'slug' => 'physics', 'color' => '#3b82f6', 'icon' => 'FiZap', 'description' => 'Classical and quantum physics'],
            ['name' => 'Literature', 'slug' => 'literature', 'color' => '#ec4899', 'icon' => 'FiBook', 'description' => 'Fiction, poetry, and literary criticism'],
            ['name' => 'History', 'slug' => 'history', 'color' => '#f59e0b', 'icon' => 'FiClock', 'description' => 'World history and civilizations'],
            ['name' => 'Science', 'slug' => 'science', 'color' => '#10b981', 'icon' => 'FiActivity', 'description' => 'Biology, chemistry, and earth sciences'],
            ['name' => 'Philosophy', 'slug' => 'philosophy', 'color' => '#6b7280', 'icon' => 'FiAward', 'description' => 'Ethics, logic, and metaphysics'],
            ['name' => 'Economics', 'slug' => 'economics', 'color' => '#f97316', 'icon' => 'FiTrendingUp', 'description' => 'Microeconomics, macroeconomics, and finance'],
        ];

        $createdCategories = [];
        foreach ($categories as $cat) {
            $createdCategories[] = Category::create($cat);
        }

        // ── Books ─────────────────────────────────────────────────────────────
        $booksData = [
            ['title' => 'Clean Code', 'author' => 'Robert C. Martin', 'isbn' => '978-0132350884', 'category' => 0, 'year' => 2008, 'qty' => 5, 'shelf' => 'A-01', 'pages' => 431, 'rating' => 4.7, 'borrows' => 142],
            ['title' => 'The Pragmatic Programmer', 'author' => 'Andrew Hunt', 'isbn' => '978-0201616224', 'category' => 0, 'year' => 1999, 'qty' => 4, 'shelf' => 'A-02', 'pages' => 352, 'rating' => 4.8, 'borrows' => 128],
            ['title' => 'Design Patterns', 'author' => 'GoF', 'isbn' => '978-0201633610', 'category' => 0, 'year' => 1994, 'qty' => 3, 'shelf' => 'A-03', 'pages' => 395, 'rating' => 4.6, 'borrows' => 95],
            ['title' => 'Introduction to Algorithms', 'author' => 'Thomas H. Cormen', 'isbn' => '978-0262033848', 'category' => 0, 'year' => 2009, 'qty' => 4, 'shelf' => 'A-04', 'pages' => 1312, 'rating' => 4.9, 'borrows' => 110],
            ['title' => 'Calculus: Early Transcendentals', 'author' => 'James Stewart', 'isbn' => '978-1285741550', 'category' => 1, 'year' => 2015, 'qty' => 6, 'shelf' => 'B-01', 'pages' => 1368, 'rating' => 4.4, 'borrows' => 89],
            ['title' => 'Linear Algebra Done Right', 'author' => 'Sheldon Axler', 'isbn' => '978-3319110790', 'category' => 1, 'year' => 2014, 'qty' => 3, 'shelf' => 'B-02', 'pages' => 340, 'rating' => 4.6, 'borrows' => 67],
            ['title' => 'A Brief History of Time', 'author' => 'Stephen Hawking', 'isbn' => '978-0553380163', 'category' => 2, 'year' => 1988, 'qty' => 5, 'shelf' => 'C-01', 'pages' => 212, 'rating' => 4.7, 'borrows' => 198],
            ['title' => 'The Feynman Lectures on Physics', 'author' => 'Richard Feynman', 'isbn' => '978-0465023820', 'category' => 2, 'year' => 1964, 'qty' => 2, 'shelf' => 'C-02', 'pages' => 1552, 'rating' => 4.9, 'borrows' => 76],
            ['title' => 'To Kill a Mockingbird', 'author' => 'Harper Lee', 'isbn' => '978-0061935466', 'category' => 3, 'year' => 1960, 'qty' => 7, 'shelf' => 'D-01', 'pages' => 336, 'rating' => 4.8, 'borrows' => 220],
            ['title' => '1984', 'author' => 'George Orwell', 'isbn' => '978-0451524935', 'category' => 3, 'year' => 1949, 'qty' => 6, 'shelf' => 'D-02', 'pages' => 328, 'rating' => 4.7, 'borrows' => 215],
            ['title' => 'Sapiens: A Brief History', 'author' => 'Yuval Noah Harari', 'isbn' => '978-0062316097', 'category' => 4, 'year' => 2011, 'qty' => 5, 'shelf' => 'E-01', 'pages' => 443, 'rating' => 4.6, 'borrows' => 185],
            ['title' => 'The Origin of Species', 'author' => 'Charles Darwin', 'isbn' => '978-0140432053', 'category' => 5, 'year' => 1859, 'qty' => 3, 'shelf' => 'F-01', 'pages' => 528, 'rating' => 4.5, 'borrows' => 62],
            ['title' => 'The Republic', 'author' => 'Plato', 'isbn' => '978-0140455113', 'category' => 6, 'year' => 380, 'qty' => 4, 'shelf' => 'G-01', 'pages' => 416, 'rating' => 4.4, 'borrows' => 45],
            ['title' => 'Thinking, Fast and Slow', 'author' => 'Daniel Kahneman', 'isbn' => '978-0374533557', 'category' => 7, 'year' => 2011, 'qty' => 4, 'shelf' => 'H-01', 'pages' => 499, 'rating' => 4.6, 'borrows' => 156],
            ['title' => 'The Wealth of Nations', 'author' => 'Adam Smith', 'isbn' => '978-0140432084', 'category' => 7, 'year' => 1776, 'qty' => 3, 'shelf' => 'H-02', 'pages' => 1264, 'rating' => 4.3, 'borrows' => 48],
        ];

        $createdBooks = [];
        foreach ($booksData as $data) {
            $available = $data['qty'] - rand(0, min(2, $data['qty']));
            $book = Book::create([
                'title' => $data['title'],
                'author' => $data['author'],
                'isbn' => $data['isbn'],
                'category_id' => $createdCategories[$data['category']]->id,
                'publication_year' => $data['year'],
                'total_quantity' => $data['qty'],
                'available_quantity' => $available,
                'shelf_location' => $data['shelf'],
                'pages' => $data['pages'],
                'rating' => $data['rating'],
                'borrow_count' => $data['borrows'],
                'is_active' => true,
                'description' => "A comprehensive resource for students and professionals. This book covers essential concepts with clear explanations and practical examples.",
            ]);
            $createdBooks[] = $book;
            $createdCategories[$data['category']]->increment('books_count');
        }

        // ── Borrowings ────────────────────────────────────────────────────────
        foreach ($students as $i => $student) {
            $book = $createdBooks[$i % count($createdBooks)];
            Borrowing::create([
                'user_id' => $student->id,
                'book_id' => $book->id,
                'borrowed_at' => Carbon::now()->subDays(rand(1, 10)),
                'due_date' => Carbon::now()->addDays(rand(3, 14)),
                'status' => 'active',
                'issued_by' => $librarian->name,
            ]);

            // Some returned
            $book2 = $createdBooks[($i + 3) % count($createdBooks)];
            Borrowing::create([
                'user_id' => $student->id,
                'book_id' => $book2->id,
                'borrowed_at' => Carbon::now()->subDays(rand(20, 40)),
                'due_date' => Carbon::now()->subDays(rand(5, 15)),
                'returned_at' => Carbon::now()->subDays(rand(1, 4)),
                'status' => 'returned',
                'issued_by' => $librarian->name,
            ]);
        }

        // ── Overdue borrowing ─────────────────────────────────────────────────
        Borrowing::create([
            'user_id' => $students[0]->id,
            'book_id' => $createdBooks[5]->id,
            'borrowed_at' => Carbon::now()->subDays(25),
            'due_date' => Carbon::now()->subDays(11),
            'status' => 'overdue',
            'fine_amount' => 55.00,
            'issued_by' => $librarian->name,
        ]);

        // ── Reservations ───────────────────────────────────────────────────────
        foreach (array_slice($students, 0, 3) as $i => $student) {
            Reservation::create([
                'user_id' => $student->id,
                'book_id' => $createdBooks[$i + 7]->id,
                'reserved_at' => Carbon::now()->subDays(rand(1, 3)),
                'expiry_date' => Carbon::now()->addDays(7),
                'status' => 'pending',
                'queue_position' => $i + 1,
            ]);
        }

        // ── Notifications ─────────────────────────────────────────────────────
        $types = ['borrow', 'return', 'overdue', 'reservation', 'system', 'recommendation'];
        foreach ($students as $student) {
            Notification::create([
                'user_id' => $student->id,
                'title' => 'Welcome to Smart Library Hub! 🎉',
                'message' => "Hello {$student->name}! Welcome to our digital library system.",
                'type' => 'system',
            ]);
            Notification::create([
                'user_id' => $student->id,
                'title' => 'Book Due Reminder ⏰',
                'message' => 'Your borrowed book is due in 3 days. Please return it on time.',
                'type' => 'overdue',
                'action_url' => '/borrowed-books',
            ]);
        }

        $this->command->info('✅ Smart Library Hub seeded successfully!');
        $this->command->info('📧 Admin: admin@library.com / password');
        $this->command->info('📧 Librarian: librarian@library.com / password');
        $this->command->info('📧 Student: alex@student.com / password');
    }
}
