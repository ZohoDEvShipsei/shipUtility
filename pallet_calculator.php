<?php
class PalletCalculator {
    // Pallet dimensions in inches
    const PALLET_WIDTH = 40;
    const PALLET_LENGTH = 48;
    const PALLET_MAX_HEIGHT = 90;
    
    /**
     * Convert centimeters to inches
     */
    private function cmToInches($cm) {
        return $cm / 2.54;
    }
    
    /**
     * Convert inches to centimeters
     */
    private function inchesToCm($inches) {
        return $inches * 2.54;
    }
    
    /**
     * Calculate how many boxes fit in one layer of the pallet
     */
    private function calculateBoxesPerLayer($boxLength, $boxWidth, $boxHeight) {
        // Try different orientations to find the best fit
        $orientations = [
            // Original orientation
            ['length' => $boxLength, 'width' => $boxWidth],
            // Rotated 90 degrees
            ['length' => $boxWidth, 'width' => $boxLength],
        ];
        
        $maxBoxes = 0;
        $bestOrientation = null;
        
        foreach ($orientations as $orientation) {
            $boxesLength = floor(self::PALLET_LENGTH / $orientation['length']);
            $boxesWidth = floor(self::PALLET_WIDTH / $orientation['width']);
            $boxesInLayer = $boxesLength * $boxesWidth;
            
            if ($boxesInLayer > $maxBoxes) {
                $maxBoxes = $boxesInLayer;
                $bestOrientation = $orientation;
            }
        }
        
        return [
            'boxes_per_layer' => $maxBoxes,
            'orientation' => $bestOrientation
        ];
    }
    
    /**
     * Calculate optimal pallet stacking
     */
    public function calculatePalletOptimization($boxLength, $boxWidth, $boxHeight, $totalBoxes, $unit = 'cm') {
        try {
            // Convert to inches if needed
            if ($unit === 'cm') {
                $boxLengthInches = $this->cmToInches($boxLength);
                $boxWidthInches = $this->cmToInches($boxWidth);
                $boxHeightInches = $this->cmToInches($boxHeight);
            } else {
                $boxLengthInches = $boxLength;
                $boxWidthInches = $boxWidth;
                $boxHeightInches = $boxHeight;
            }
            
            // Validate dimensions
            if ($boxLengthInches <= 0 || $boxWidthInches <= 0 || $boxHeightInches <= 0) {
                throw new Exception('Las dimensiones de la caja deben ser mayores a 0');
            }
            
            if ($boxLengthInches > self::PALLET_LENGTH || $boxWidthInches > self::PALLET_WIDTH) {
                throw new Exception('Las dimensiones de la caja exceden el tamaño del pallet');
            }
            
            // Calculate boxes per layer
            $layerInfo = $this->calculateBoxesPerLayer($boxLengthInches, $boxWidthInches, $boxHeightInches);
            $boxesPerLayer = $layerInfo['boxes_per_layer'];
            
            if ($boxesPerLayer === 0) {
                throw new Exception('Las cajas no caben en el pallet');
            }
            
            // Calculate layers per pallet
            $layersPerPallet = floor(self::PALLET_MAX_HEIGHT / $boxHeightInches);
            
            // Calculate total boxes per pallet
            $boxesPerPallet = $boxesPerLayer * $layersPerPallet;
            
            // Calculate total pallets needed
            $totalPallets = ceil($totalBoxes / $boxesPerPallet);
            
            // Calculate remaining boxes
            $remainingBoxes = $totalBoxes % $boxesPerPallet;
            if ($remainingBoxes === 0) {
                $remainingBoxes = $boxesPerPallet;
            }
            
            // Calculate actual height used
            $actualLayers = ceil($totalBoxes / $boxesPerLayer);
            $actualHeight = $actualLayers * $boxHeightInches;
            
            return [
                'success' => true,
                'data' => [
                    'box_dimensions' => [
                        'length' => $boxLengthInches,
                        'width' => $boxWidthInches,
                        'height' => $boxHeightInches,
                        'unit' => 'inches'
                    ],
                    'pallet_dimensions' => [
                        'length' => self::PALLET_LENGTH,
                        'width' => self::PALLET_WIDTH,
                        'max_height' => self::PALLET_MAX_HEIGHT
                    ],
                    'optimization' => [
                        'boxes_per_layer' => $boxesPerLayer,
                        'layers_per_pallet' => $layersPerPallet,
                        'boxes_per_pallet' => $boxesPerPallet,
                        'total_pallets' => $totalPallets,
                        'remaining_boxes' => $remainingBoxes,
                        'actual_height' => $actualHeight,
                        'orientation' => $layerInfo['orientation']
                    ]
                ]
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'calculate') {
    header('Content-Type: application/json');
    
    try {
        $length = floatval($_POST['length'] ?? 0);
        $width = floatval($_POST['width'] ?? 0);
        $height = floatval($_POST['height'] ?? 0);
        $quantity = intval($_POST['quantity'] ?? 0);
        $unit = $_POST['unit'] ?? 'cm';
        
        if ($length <= 0 || $width <= 0 || $height <= 0) {
            throw new Exception('Todas las dimensiones deben ser mayores a 0');
        }
        
        if ($quantity <= 0) {
            throw new Exception('La cantidad debe ser mayor a 0');
        }
        
        $calculator = new PalletCalculator();
        $result = $calculator->calculatePalletOptimization($length, $width, $height, $quantity, $unit);
        
        echo json_encode($result);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calculadora de Optimización de Pallets</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
        }
        
        .header p {
            font-size: 1.1em;
            opacity: 0.9;
        }
        
        .content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            padding: 30px;
        }
        
        .input-section {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 15px;
            border: 2px solid #e9ecef;
        }
        
        .input-section h2 {
            color: #2c3e50;
            margin-bottom: 20px;
            font-size: 1.5em;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #495057;
        }
        
        .form-group input, .form-group select {
            width: 100%;
            padding: 12px;
            border: 2px solid #dee2e6;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s ease;
        }
        
        .form-group input:focus, .form-group select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .dimensions-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 15px;
        }
        
        .calculate-btn {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s ease;
        }
        
        .calculate-btn:hover {
            transform: translateY(-2px);
        }
        
        .calculate-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        
        .results-section {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 15px;
            border: 2px solid #e9ecef;
        }
        
        .results-section h2 {
            color: #2c3e50;
            margin-bottom: 20px;
            font-size: 1.5em;
        }
        
        .result-item {
            background: white;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 10px;
            border-left: 4px solid #667eea;
        }
        
        .result-item h3 {
            color: #2c3e50;
            margin-bottom: 8px;
            font-size: 1.1em;
        }
        
        .result-item p {
            color: #6c757d;
            font-size: 1.2em;
            font-weight: 600;
        }
        
        .highlight {
            color: #667eea;
            font-size: 1.4em;
        }
        
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 10px;
            border: 1px solid #f5c6cb;
        }
        
        .loading {
            text-align: center;
            color: #6c757d;
            font-style: italic;
        }
        
        .pallet-info {
            background: #e3f2fd;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        .pallet-info h3 {
            color: #1976d2;
            margin-bottom: 10px;
        }
        
        .pallet-info p {
            color: #424242;
            margin-bottom: 5px;
        }
        
        @media (max-width: 768px) {
            .content {
                grid-template-columns: 1fr;
            }
            
            .dimensions-grid {
                grid-template-columns: 1fr;
            }
            
            .header h1 {
                font-size: 2em;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📦 Calculadora de Pallets</h1>
            <p>Optimiza el empaquetado de tus cajas en pallets estándar</p>
        </div>
        
        <div class="content">
            <div class="input-section">
                <h2>📏 Dimensiones de la Caja</h2>
                
                <form id="calculatorForm">
                    <div class="dimensions-grid">
                        <div class="form-group">
                            <label for="length">Largo</label>
                            <input type="number" id="length" name="length" step="0.01" min="0.01" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="width">Ancho</label>
                            <input type="number" id="width" name="width" step="0.01" min="0.01" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="height">Alto</label>
                            <input type="number" id="height" name="height" step="0.01" min="0.01" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="unit">Unidad de Medida</label>
                        <select id="unit" name="unit">
                            <option value="cm">Centímetros (cm)</option>
                            <option value="in">Pulgadas (in)</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="quantity">Cantidad Total de Cajas</label>
                        <input type="number" id="quantity" name="quantity" min="1" required>
                    </div>
                    
                    <button type="submit" class="calculate-btn" id="calculateBtn">
                        🧮 Calcular Optimización
                    </button>
                </form>
                
                <div class="pallet-info">
                    <h3>📋 Especificaciones del Pallet</h3>
                    <p><strong>Dimensiones:</strong> 48" × 40" (largo × ancho)</p>
                    <p><strong>Altura máxima:</strong> 90 pulgadas</p>
                    <p><strong>Tipo:</strong> Pallet estándar americano</p>
                </div>
            </div>
            
            <div class="results-section">
                <h2>📊 Resultados</h2>
                <div id="results">
                    <p class="loading">Ingresa las dimensiones y cantidad para ver los resultados</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('calculatorForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('action', 'calculate');
            
            const calculateBtn = document.getElementById('calculateBtn');
            const resultsDiv = document.getElementById('results');
            
            // Show loading state
            calculateBtn.disabled = true;
            calculateBtn.textContent = '🔄 Calculando...';
            resultsDiv.innerHTML = '<p class="loading">Calculando optimización...</p>';
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const result = data.data;
                    const unit = document.getElementById('unit').value;
                    const unitLabel = unit === 'cm' ? 'cm' : 'pulgadas';
                    
                    resultsDiv.innerHTML = `
                        <div class="result-item">
                            <h3>📦 Cajas por Capa</h3>
                            <p class="highlight">${result.optimization.boxes_per_layer} cajas</p>
                        </div>
                        
                        <div class="result-item">
                            <h3>🏗️ Capas por Pallet</h3>
                            <p class="highlight">${result.optimization.layers_per_pallet} capas</p>
                        </div>
                        
                        <div class="result-item">
                            <h3>📦 Cajas por Pallet</h3>
                            <p class="highlight">${result.optimization.boxes_per_pallet} cajas</p>
                        </div>
                        
                        <div class="result-item">
                            <h3>🚛 Pallets Necesarios</h3>
                            <p class="highlight">${result.optimization.total_pallets} pallets</p>
                        </div>
                        
                        <div class="result-item">
                            <h3>📋 Cajas Restantes</h3>
                            <p class="highlight">${result.optimization.remaining_boxes} cajas</p>
                        </div>
                        
                        <div class="result-item">
                            <h3>📏 Altura Utilizada</h3>
                            <p class="highlight">${result.optimization.actual_height.toFixed(2)} pulgadas</p>
                        </div>
                        
                        <div class="result-item">
                            <h3>🔄 Orientación Óptima</h3>
                            <p>Largo: ${result.optimization.orientation.length.toFixed(2)} pulgadas</p>
                            <p>Ancho: ${result.optimization.orientation.width.toFixed(2)} pulgadas</p>
                        </div>
                    `;
                } else {
                    resultsDiv.innerHTML = `
                        <div class="error">
                            <strong>❌ Error:</strong> ${data.message}
                        </div>
                    `;
                }
            })
            .catch(error => {
                resultsDiv.innerHTML = `
                    <div class="error">
                        <strong>❌ Error:</strong> Error de conexión
                    </div>
                `;
            })
            .finally(() => {
                // Reset button
                calculateBtn.disabled = false;
                calculateBtn.textContent = '🧮 Calcular Optimización';
            });
        });
        
        // Add input validation
        document.querySelectorAll('input[type="number"]').forEach(input => {
            input.addEventListener('input', function() {
                if (this.value < 0) {
                    this.value = 0;
                }
            });
        });
    </script>
</body>
</html> 
