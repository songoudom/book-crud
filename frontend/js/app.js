const API_URL = 'http://localhost/y3/book-crud/backend/api.php';

// Load books on page load
document.addEventListener('DOMContentLoaded', function() {
    if (window.location.pathname.includes('dashboard.html')) {
        loadDashboard();
    } else {
        loadBooks();
    }
});

// Load all books
async function loadBooks() {
    try {
        const response = await fetch(`${API_URL}/books`);
        const data = await response.json();
        
        if (data.success) {
            displayBooks(data.data);
        } else {
            showError('Failed to load books');
        }
    } catch (error) {
        console.error('Error:', error);
        showError('Failed to load books');
    }
}

// Display books in grid
function displayBooks(books) {
    const grid = document.getElementById('booksGrid');
    
    if (books.length === 0) {
        grid.innerHTML = '<p class="no-books">No books found. Add your first book!</p>';
        return;
    }
    
    grid.innerHTML = books.map(book => {
        const progress = book.pages ? Math.round((book.current_page / book.pages) * 100) : 0;
        const statusClass = getStatusClass(book.status);
        
        return `
            <div class="book-card" data-id="${book.id}">
                <h3 class="book-title">${escapeHtml(book.title)}</h3>
                <p class="book-author">by ${escapeHtml(book.author)}</p>
                <div class="book-meta">
                    <span>${book.genre || 'No genre'}</span>
                    <span>${book.publication_year || 'Unknown year'}</span>
                </div>
                <span class="book-status ${statusClass}">${book.status}</span>
                
                ${book.pages ? `
                    <div class="book-progress">
                        <div class="progress-bar" style="width: ${progress}%"></div>
                    </div>
                    <p>${book.current_page || 0} / ${book.pages} pages (${progress}%)</p>
                ` : ''}
                
                <div class="book-actions">
                    <button class="btn btn-small btn-primary" onclick="showEditBook(${book.id})">Edit</button>
                    <button class="btn btn-small" onclick="showAddSession(${book.id})">Add Session</button>
                    <button class="btn btn-small btn-danger" onclick="deleteBook(${book.id})">Delete</button>
                </div>
            </div>
        `;
    }).join('');
}

function getStatusClass(status) {
    switch(status) {
        case 'Reading': return 'status-reading';
        case 'Completed': return 'status-completed';
        case 'To Read': return 'status-toread';
        default: return '';
    }
}

// Filter books
function filterBooks() {
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
    const statusFilter = document.getElementById('statusFilter').value;
    
    document.querySelectorAll('.book-card').forEach(card => {
        const title = card.querySelector('.book-title').textContent.toLowerCase();
        const author = card.querySelector('.book-author').textContent.toLowerCase();
        const status = card.querySelector('.book-status').textContent;
        
        const matchesSearch = title.includes(searchTerm) || author.includes(searchTerm);
        const matchesStatus = !statusFilter || status === statusFilter;
        
        card.style.display = matchesSearch && matchesStatus ? 'block' : 'none';
    });
}

// Modal functions
function showAddBookModal() {
    document.getElementById('modalTitle').textContent = 'Add New Book';
    document.getElementById('bookForm').reset();
    document.getElementById('bookId').value = '';
    document.getElementById('bookModal').classList.add('show');
}

function closeModal() {
    document.getElementById('bookModal').classList.remove('show');
}

function showAddSession(bookId) {
    document.getElementById('sessionBookId').value = bookId;
    document.getElementById('sessionDate').valueAsDate = new Date();
    document.getElementById('sessionModal').classList.add('show');
}

function closeSessionModal() {
    document.getElementById('sessionModal').classList.remove('show');
}

// Save book
async function saveBook(event) {
    event.preventDefault();
    
    const bookId = document.getElementById('bookId').value;
    const bookData = {
        title: document.getElementById('title').value,
        author: document.getElementById('author').value,
        isbn: document.getElementById('isbn').value,
        genre: document.getElementById('genre').value,
        publication_year: document.getElementById('publication_year').value,
        pages: document.getElementById('pages').value,
        status: document.getElementById('status').value
    };
    
    try {
        const url = bookId ? `${API_URL}/books/${bookId}` : `${API_URL}/books`;
        const method = bookId ? 'PUT' : 'POST';
        
        const response = await fetch(url, {
            method: method,
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(bookData)
        });
        
        const data = await response.json();
        
        if (data.success) {
            showSuccess(bookId ? 'Book updated successfully!' : 'Book added successfully!');
            document.getElementById('bookForm').reset();
            document.getElementById('bookId').value = '';
            if (window.location.pathname.includes('dashboard.html')) {
                loadDashboard();
            } else {
                loadBooks();
            }
        } else {
            showError(data.message || 'Failed to save book');
        }
    } catch (error) {
        console.error('Error:', error);
        showError('Failed to save book');
    }
}

// Show success message
function showSuccess(message) {
    alert(message);
}

// Show error message
function showError(message) {
    alert('Error: ' + message);
}

// Load dashboard data
async function loadDashboard() {
    try {
        const response = await fetch(`${API_URL}/books`);
        const data = await response.json();
        
        if (data.success) {
            // Update stats
            document.getElementById('totalBooks').textContent = data.data.length;
            document.getElementById('readingCount').textContent = data.data.filter(book => book.status === 'Reading').length;
            // For now, set other stats to 0
            document.getElementById('monthlyPages').textContent = '0';
            document.getElementById('avgRating').textContent = '0.0';
        } else {
            showError('Failed to load dashboard data');
        }
    } catch (error) {
        console.error('Error:', error);
        showError('Failed to load dashboard data');
    }
}

// Escape HTML to prevent XSS
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}