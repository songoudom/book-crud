<?php
require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];
$request = explode('/', trim($_SERVER['PATH_INFO'] ?? '', '/'));
$resource = $request[0] ?? '';
$id = $request[1] ?? null;

switch($method) {
    case 'GET':
        if ($resource === 'books') {
            if ($id) {
                getBook($id);
            } else {
                getAllBooks();
            }
        } elseif ($resource === 'dashboard') {
            getDashboardData();
        }
        break;
        
    case 'POST':
        if ($resource === 'books') {
            createBook();
        } elseif ($resource === 'sessions') {
            addReadingSession();
        }
        break;
        
    case 'PUT':
        if ($resource === 'books' && $id) {
            updateBook($id);
        }
        break;
        
    case 'DELETE':
        if ($resource === 'books' && $id) {
            deleteBook($id);
        }
        break;
}

function getAllBooks() {
    global $pdo;
    try {
        $stmt = $pdo->query("SELECT * FROM books ORDER BY created_at DESC");
        $books = $stmt->fetchAll();
        echo json_encode(['success' => true, 'data' => $books]);
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function getBook($id) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT * FROM books WHERE id = ?");
        $stmt->execute([$id]);
        $book = $stmt->fetch();
        
        // Get reading sessions for this book
        $stmt = $pdo->prepare("SELECT * FROM reading_sessions WHERE book_id = ? ORDER BY session_date DESC");
        $stmt->execute([$id]);
        $sessions = $stmt->fetchAll();
        
        echo json_encode(['success' => true, 'data' => ['book' => $book, 'sessions' => $sessions]]);
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function getDashboardData() {
    global $pdo;
    try {
        // Statistics
        $stats = [];
        
        // Total books
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM books");
        $stats['total_books'] = $stmt->fetch()['total'];
        
        // Books by status
        $stmt = $pdo->query("SELECT status, COUNT(*) as count FROM books GROUP BY status");
        $stats['by_status'] = $stmt->fetchAll();
        
        // Currently reading
        $stmt = $pdo->query("SELECT * FROM books WHERE status = 'Reading'");
        $stats['currently_reading'] = $stmt->fetchAll();
        
        // Recent activity
        $stmt = $pdo->query("
            SELECT rs.*, b.title, b.author 
            FROM reading_sessions rs 
            JOIN books b ON rs.book_id = b.id 
            ORDER BY rs.session_date DESC 
            LIMIT 10
        ");
        $stats['recent_activity'] = $stmt->fetchAll();
        
        // Reading progress this month
        $stmt = $pdo->query("
            SELECT SUM(pages_read) as total_pages, SUM(minutes_read) as total_minutes 
            FROM reading_sessions 
            WHERE MONTH(session_date) = MONTH(CURRENT_DATE())
        ");
        $stats['monthly_progress'] = $stmt->fetch();
        
        // Average rating
        $stmt = $pdo->query("SELECT AVG(rating) as avg_rating FROM books WHERE rating IS NOT NULL");
        $stats['avg_rating'] = $stmt->fetch()['avg_rating'];
        
        echo json_encode(['success' => true, 'data' => $stats]);
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function createBook() {
    global $pdo;
    $data = json_decode(file_get_contents('php://input'), true);
    
    try {
        $sql = "INSERT INTO books (title, author, isbn, genre, publication_year, pages, status) 
                VALUES (:title, :author, :isbn, :genre, :publication_year, :pages, :status)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':title' => $data['title'],
            ':author' => $data['author'],
            ':isbn' => $data['isbn'] ?? null,
            ':genre' => $data['genre'] ?? null,
            ':publication_year' => $data['publication_year'] ?? null,
            ':pages' => $data['pages'] ?? null,
            ':status' => $data['status'] ?? 'To Read'
        ]);
        
        echo json_encode(['success' => true, 'message' => 'Book created successfully', 'id' => $pdo->lastInsertId()]);
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function updateBook($id) {
    global $pdo;
    $data = json_decode(file_get_contents('php://input'), true);
    
    try {
        $sql = "UPDATE books SET 
                title = :title,
                author = :author,
                isbn = :isbn,
                genre = :genre,
                publication_year = :publication_year,
                pages = :pages,
                current_page = :current_page,
                status = :status,
                rating = :rating,
                notes = :notes
                WHERE id = :id";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':id' => $id,
            ':title' => $data['title'],
            ':author' => $data['author'],
            ':isbn' => $data['isbn'] ?? null,
            ':genre' => $data['genre'] ?? null,
            ':publication_year' => $data['publication_year'] ?? null,
            ':pages' => $data['pages'] ?? null,
            ':current_page' => $data['current_page'] ?? 0,
            ':status' => $data['status'],
            ':rating' => $data['rating'] ?? null,
            ':notes' => $data['notes'] ?? null
        ]);
        
        echo json_encode(['success' => true, 'message' => 'Book updated successfully']);
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function deleteBook($id) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("DELETE FROM books WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['success' => true, 'message' => 'Book deleted successfully']);
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function addReadingSession() {
    global $pdo;
    $data = json_decode(file_get_contents('php://input'), true);
    
    try {
        $pdo->beginTransaction();
        
        // Add reading session
        $sql = "INSERT INTO reading_sessions (book_id, session_date, pages_read, minutes_read, notes) 
                VALUES (:book_id, :session_date, :pages_read, :minutes_read, :notes)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':book_id' => $data['book_id'],
            ':session_date' => $data['session_date'],
            ':pages_read' => $data['pages_read'],
            ':minutes_read' => $data['minutes_read'],
            ':notes' => $data['notes'] ?? null
        ]);
        
        // Update book's current page
        $stmt = $pdo->prepare("UPDATE books SET current_page = current_page + ? WHERE id = ?");
        $stmt->execute([$data['pages_read'], $data['book_id']]);
        
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Reading session added']);
    } catch(PDOException $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
?>