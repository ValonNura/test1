<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'database.php';

class Product {
    private $db;

    public function __construct() {
        $database = new Database('localhost', 'projekti', 'root', 'loni1234');
        $this->db = $database->connect();

       
        if (!file_exists('uploads')) {
            mkdir('uploads', 0777, true);
        }
    }

    
    public function productExists($name) {
        $sql = "SELECT COUNT(*) FROM products WHERE name = :name";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':name', $name);
        $stmt->execute();

        
        return $stmt->fetchColumn() > 0;
    }

    
    public function addProduct($data, $files) {
        
        if ($this->productExists($data['name'])) {
            return "Error: Product with this name already exists.";
        }

        
        if ($data['price'] <= 0) {
            return "Invalid price.";
        }
        if ($data['rating'] < 1 || $data['rating'] > 5) {
            return "Invalid rating.";
        }

       
        $defaultImagePath = 'uploads/' . basename($files['image_default']['name']);
        $hoverImagePath = 'uploads/' . basename($files['image_hover']['name']);

        $allowedTypes = ['image/jpeg', 'image/png'];
        if (!in_array($files['image_default']['type'], $allowedTypes) || !in_array($files['image_hover']['type'], $allowedTypes)) {
            return "Invalid image type. Only JPEG and PNG are allowed.";
        }

        
        if ($files['image_default']['error'] != UPLOAD_ERR_OK) {
            return "Error uploading default image.";
        }

        if ($files['image_hover']['error'] != UPLOAD_ERR_OK) {
            return "Error uploading hover image.";
        }

       
        if (!move_uploaded_file($files['image_default']['tmp_name'], $defaultImagePath)) {
            return "Failed to upload default image.";
        }

        if (!move_uploaded_file($files['image_hover']['tmp_name'], $hoverImagePath)) {
            return "Failed to upload hover image.";
        }

        
        try {
            $sql = "INSERT INTO products (name, category, dimensions, price, old_price, image_default, image_hover, is_best_seller, rating)
                    VALUES (:name, :category, :dimensions, :price, :old_price, :image_default, :image_hover, :is_best_seller, :rating)";
            
            $stmt = $this->db->prepare($sql);

            
            $stmt->bindParam(':name', $data['name']);
            $stmt->bindParam(':category', $data['category']);
            $stmt->bindParam(':dimensions', $data['dimensions']);
            $stmt->bindParam(':price', $data['price']);
            $stmt->bindParam(':old_price', $data['old_price']);
            $stmt->bindParam(':image_default', $defaultImagePath);
            $stmt->bindParam(':image_hover', $hoverImagePath);
            $stmt->bindParam(':is_best_seller', $data['is_best_seller']);
            $stmt->bindParam(':rating', $data['rating']);

            
            if ($stmt->execute()) {
                return "Product added successfully!";
            } else {
                return "Failed to add product.";
            }
        } catch (PDOException $e) {
            return "Database Error: " . $e->getMessage();
        }
    }
}


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    $productHandler = new Product();
    $message = $productHandler->addProduct($_POST, $_FILES);
    
   
    header("Location: add_product.php?message=" . urlencode($message));
    exit();
}
?>
