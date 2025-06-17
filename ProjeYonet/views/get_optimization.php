<?php
require_once '../config/config.php';
require_once '../models/GeminiHelper.php';

header('Content-Type: application/json');

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if(isset($data['project_type']) && isset($data['description'])) {
        try {
            $gemini = new GeminiHelper(GEMINI_API_KEY);
            $optimization = $gemini->getProjectOptimization([
                'type' => $data['project_type'],
                'description' => $data['description']
            ]);
            
            echo json_encode([
                'success' => true,
                'content' => $optimization
            ]);
        } catch(Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Gerekli parametreler eksik'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'error' => 'Geçersiz istek metodu'
    ]);
} 