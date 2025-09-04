<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Create blog_posts table if it doesn't exist
try {
    $db->exec("
        CREATE TABLE IF NOT EXISTS blog_posts (
            id VARCHAR(36) NOT NULL PRIMARY KEY DEFAULT (UUID()),
            title VARCHAR(255) NOT NULL,
            slug VARCHAR(255) NOT NULL UNIQUE,
            excerpt TEXT DEFAULT NULL,
            content LONGTEXT NOT NULL,
            featured_image VARCHAR(500) DEFAULT NULL,
            author_id VARCHAR(36) NOT NULL,
            category VARCHAR(100) DEFAULT 'general',
            tags JSON DEFAULT NULL,
            status ENUM('draft', 'published', 'archived') DEFAULT 'draft',
            published_at TIMESTAMP NULL DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_blog_posts_slug (slug),
            INDEX idx_blog_posts_status (status),
            INDEX idx_blog_posts_category (category),
            INDEX idx_blog_posts_published (published_at),
            FOREIGN KEY (author_id) REFERENCES user_profiles(user_id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error configurando tabla de blog: ' . $e->getMessage()]);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        handleGetPosts();
        break;
    case 'POST':
        handleCreatePost();
        break;
    case 'PUT':
        handleUpdatePost();
        break;
    case 'DELETE':
        handleDeletePost();
        break;
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Método no permitido']);
}

function handleGetPosts() {
    global $db;
    
    try {
        $stmt = $db->prepare("
            SELECT 
                bp.*,
                CONCAT(up.first_name, ' ', up.last_name) as author_name
            FROM blog_posts bp
            LEFT JOIN user_profiles up ON bp.author_id = up.user_id
            ORDER BY bp.created_at DESC
        ");
        $stmt->execute();
        $posts = $stmt->fetchAll();
        
        // Decode tags for each post
        foreach ($posts as &$post) {
            $post['tags'] = json_decode($post['tags'], true) ?: [];
        }
        
        echo json_encode(['success' => true, 'posts' => $posts]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error al cargar artículos: ' . $e->getMessage()]);
    }
}

function handleCreatePost() {
    global $db;
    
    // Require admin access
    if (!isAdmin()) {
        http_response_code(403);
        echo json_encode(['error' => 'Acceso denegado']);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validate required fields
    $required = ['title', 'content', 'excerpt', 'category'];
    foreach ($required as $field) {
        if (empty($input[$field])) {
            http_response_code(400);
            echo json_encode(['error' => "Campo requerido: $field"]);
            return;
        }
    }
    
    try {
        // Generate slug from title
        $slug = generateSlug($input['title']);
        
        // Check if slug already exists
        $stmt = $db->prepare("SELECT id FROM blog_posts WHERE slug = ?");
        $stmt->execute([$slug]);
        if ($stmt->fetch()) {
            $slug = $slug . '-' . time();
        }
        
        // Process tags
        $tags = [];
        if (!empty($input['tags'])) {
            $tags = array_map('trim', explode(',', $input['tags']));
            $tags = array_filter($tags);
        }
        
        // Set published_at if status is published
        $publishedAt = null;
        if ($input['status'] === 'published') {
            $publishedAt = date('Y-m-d H:i:s');
        }
        
        // Create post
        $stmt = $db->prepare("
            INSERT INTO blog_posts (
                title, slug, excerpt, content, featured_image, author_id, 
                category, tags, status, published_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $input['title'],
            $slug,
            $input['excerpt'],
            $input['content'],
            $input['featured_image'] ?? null,
            $_SESSION['user_id'],
            $input['category'],
            json_encode($tags),
            $input['status'],
            $publishedAt
        ]);
        
        echo json_encode(['success' => true, 'message' => 'Artículo creado exitosamente']);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error al crear artículo: ' . $e->getMessage()]);
    }
}

function handleUpdatePost() {
    global $db;
    
    // Require admin access
    if (!isAdmin()) {
        http_response_code(403);
        echo json_encode(['error' => 'Acceso denegado']);
        return;
    }
    
    $postId = $_GET['id'] ?? '';
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (empty($postId)) {
        http_response_code(400);
        echo json_encode(['error' => 'ID de artículo requerido']);
        return;
    }
    
    try {
        // Process tags
        $tags = [];
        if (!empty($input['tags'])) {
            if (is_string($input['tags'])) {
                $tags = array_map('trim', explode(',', $input['tags']));
            } else {
                $tags = $input['tags'];
            }
            $tags = array_filter($tags);
        }
        
        // Set published_at if changing to published
        $publishedAt = null;
        if ($input['status'] === 'published') {
            // Check if it was already published
            $stmt = $db->prepare("SELECT published_at FROM blog_posts WHERE id = ?");
            $stmt->execute([$postId]);
            $currentPost = $stmt->fetch();
            
            $publishedAt = $currentPost['published_at'] ?: date('Y-m-d H:i:s');
        }
        
        // Update post
        $stmt = $db->prepare("
            UPDATE blog_posts 
            SET title = ?, excerpt = ?, content = ?, featured_image = ?, 
                category = ?, tags = ?, status = ?, published_at = ?
            WHERE id = ?
        ");
        
        $stmt->execute([
            $input['title'],
            $input['excerpt'],
            $input['content'],
            $input['featured_image'] ?? null,
            $input['category'],
            json_encode($tags),
            $input['status'],
            $publishedAt,
            $postId
        ]);
        
        echo json_encode(['success' => true, 'message' => 'Artículo actualizado exitosamente']);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error al actualizar artículo: ' . $e->getMessage()]);
    }
}

function handleDeletePost() {
    global $db;
    
    // Require admin access
    if (!isAdmin()) {
        http_response_code(403);
        echo json_encode(['error' => 'Acceso denegado']);
        return;
    }
    
    $postId = $_GET['id'] ?? '';
    
    if (empty($postId)) {
        http_response_code(400);
        echo json_encode(['error' => 'ID de artículo requerido']);
        return;
    }
    
    try {
        $stmt = $db->prepare("DELETE FROM blog_posts WHERE id = ?");
        $stmt->execute([$postId]);
        
        echo json_encode(['success' => true, 'message' => 'Artículo eliminado exitosamente']);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error al eliminar artículo: ' . $e->getMessage()]);
    }
}

function generateSlug($title) {
    // Convert to lowercase
    $slug = strtolower($title);
    
    // Replace special characters
    $slug = str_replace(['á', 'é', 'í', 'ó', 'ú', 'ñ'], ['a', 'e', 'i', 'o', 'u', 'n'], $slug);
    
    // Remove non-alphanumeric characters except spaces and hyphens
    $slug = preg_replace('/[^a-z0-9\s\-]/', '', $slug);
    
    // Replace spaces and multiple hyphens with single hyphen
    $slug = preg_replace('/[\s\-]+/', '-', $slug);
    
    // Remove leading/trailing hyphens
    $slug = trim($slug, '-');
    
    return $slug;
}
?>