<?php

/**
 * Pure PHP PCA Implementation for Educational Purposes
 */

class PCA
{
    /**
     * @param array $data 2D array [row][col]
     * @param int $nComponents Number of components to keep
     * @return array ['projected' => [], 'eigenvalues' => [], 'eigenvectors' => []]
     */
    public function fitTransform(array $data, int $nComponents): array
    {
        $rows = count($data);
        $cols = count($data[0]);

        // 1. Standardize the Data (Mean Centering & Unit Variance)
        $means = [];
        $stdDevs = [];
        
        // Calculate Mean
        for ($j = 0; $j < $cols; $j++) {
            $sum = 0;
            for ($i = 0; $i < $rows; $i++) {
                $sum += $data[$i][$j];
            }
            $means[$j] = $sum / $rows;
        }

        // Calculate StdDev
        for ($j = 0; $j < $cols; $j++) {
            $sumSq = 0;
            for ($i = 0; $i < $rows; $i++) {
                $diff = $data[$i][$j] - $means[$j];
                $sumSq += $diff * $diff;
            }
            $stdDevs[$j] = sqrt($sumSq / ($rows - 1)); // Sample StdDev
        }

        // Z-Score Standardization
        $normalizedData = [];
        for ($i = 0; $i < $rows; $i++) {
            $row = [];
            for ($j = 0; $j < $cols; $j++) {
                $row[$j] = ($stdDevs[$j] != 0) ? ($data[$i][$j] - $means[$j]) / $stdDevs[$j] : 0;
            }
            $normalizedData[$i] = $row;
        }

        // 2. Compute Covariance Matrix
        // Cov(X, Y) = Sum((x - meanX) * (y - meanY)) / (N - 1)
        // Since data is already centered/standardized, mean is 0.
        $covarianceMatrix = [];
        for ($j = 0; $j < $cols; $j++) {
            for ($k = 0; $k < $cols; $k++) {
                $sum = 0;
                for ($i = 0; $i < $rows; $i++) {
                    $sum += $normalizedData[$i][$j] * $normalizedData[$i][$k];
                }
                $covarianceMatrix[$j][$k] = $sum / ($rows - 1);
            }
        }

        // 3. Eigendecomposition (Jacobi Algorithm for Symmetric Matrices)
        // We find Eigenvalues and Eigenvectors of the Covariance Matrix
        list($eigenvalues, $eigenvectors) = $this->jacobiEigenvalueAlgorithm($covarianceMatrix);

        // 4. Sort Eigenpairs
        // Combine into pairs to sort together
        $pairs = [];
        foreach ($eigenvalues as $i => $val) {
            $pairs[] = ['value' => $val, 'vector' => array_column($eigenvectors, $i)];
        }

        // Sort descending by eigenvalue
        usort($pairs, function ($a, $b) {
            return $b['value'] <=> $a['value'];
        });

        // 5. Projection
        // Take top N vectors
        $topVectors = [];
        for ($k = 0; $k < $nComponents; $k++) {
            $topVectors[] = $pairs[$k]['vector'];
        }

        // Project: Matrix Multiply NormalizedData (NxM) * TopVectors^T (MxK)
        // Note: TopVectors is (KxM) here effectively, so we need to dot product rows
        $projected = [];
        for ($i = 0; $i < $rows; $i++) {
            $newRow = [];
            for ($k = 0; $k < $nComponents; $k++) {
                $dot = 0;
                $vector = $topVectors[$k];
                for ($j = 0; $j < $cols; $j++) {
                    $dot += $normalizedData[$i][$j] * $vector[$j];
                }
                $newRow[] = $dot;
            }
            $projected[] = $newRow;
        }

        return [
            'projected' => $projected,
            'eigenvalues' => array_column($pairs, 'value'),
            // 'eigenvectors' => $topVectors // Transposed actually
        ];
    }

    /**
     * Jacobi Eigenvalue Algorithm for real symmetric matrices.
     * Returns [$eigenvalues, $eigenvectors]
     * $eigenvalues is a 1D array
     * $eigenvectors is a 2D array where columns are eigenvectors
     */
    private function jacobiEigenvalueAlgorithm(array $A, float $tol = 1e-10, int $maxIter = 100): array
    {
        $n = count($A);
        // Initialize V as identity matrix
        $V = [];
        for ($i = 0; $i < $n; $i++) {
            for ($j = 0; $j < $n; $j++) {
                $V[$i][$j] = ($i === $j) ? 1.0 : 0.0;
            }
        }

        $iterations = 0;
        while ($iterations < $maxIter) {
            // Find max off-diagonal element
            $maxAbs = 0.0;
            $p = 0;
            $q = 0;
            for ($i = 0; $i < $n; $i++) {
                for ($j = $i + 1; $j < $n; $j++) {
                    if (abs($A[$i][$j]) > $maxAbs) {
                        $maxAbs = abs($A[$i][$j]);
                        $p = $i;
                        $q = $j;
                    }
                }
            }

            if ($maxAbs < $tol) {
                break; // Converged
            }

            // Calculate rotation angle
            $app = $A[$p][$p];
            $aqq = $A[$q][$q];
            $apq = $A[$p][$q];

            $theta = 0.5 * atan2(2 * $apq, $aqq - $app);
            $c = cos($theta);
            $s = sin($theta);

            // Update A (rotate)
            // A' = J^T * A * J
            // We only need to update relevant rows/cols p and q
            
            // 1. Update diagonal elements
            $newApp = $c*$c*$app - 2*$s*$c*$apq + $s*$s*$aqq;
            $newAqq = $s*$s*$app + 2*$s*$c*$apq + $c*$c*$aqq;
            
            $A[$p][$p] = $newApp;
            $A[$q][$q] = $newAqq;
            $A[$p][$q] = 0; // Theoretically 0
            $A[$q][$p] = 0;

            // 2. Update other elements in rows/cols p and q
            for ($i = 0; $i < $n; $i++) {
                if ($i != $p && $i != $q) {
                    $api = $A[$p][$i];
                    $aqi = $A[$q][$i];
                    
                    $A[$p][$i] = $c*$api - $s*$aqi;
                    $A[$i][$p] = $A[$p][$i]; // Symmetry
                    
                    $A[$q][$i] = $s*$api + $c*$aqi;
                    $A[$i][$q] = $A[$q][$i]; // Symmetry
                }
            }

            // Update Eigenvectors V
            // V' = V * J
            for ($i = 0; $i < $n; $i++) {
                $vip = $V[$i][$p];
                $viq = $V[$i][$q];
                $V[$i][$p] = $c*$vip - $s*$viq;
                $V[$i][$q] = $s*$vip + $c*$viq;
            }

            $iterations++;
        }

        $eigenvalues = [];
        for ($i = 0; $i < $n; $i++) {
            $eigenvalues[$i] = $A[$i][$i];
        }

        return [$eigenvalues, $V];
    }
}

// --- Verification ---

// Simple Dataset (Correlated Variable)
// y = 2x + noise
$data = [
    [1, 2.2],
    [2, 3.8],
    [3, 6.1],
    [4, 8.0],
    [5, 10.1]
];

echo "Original Data:\n";
foreach ($data as $row) echo "[" . implode(", ", $row) . "]\n";

$pca = new PCA();
$result = $pca->fitTransform($data, 1);

echo "\nCalculated Eigenvalues:\n";
foreach ($result['eigenvalues'] as $ev) echo $ev . "\n";

echo "\nProjected Data (1 Component):\n";
foreach ($result['projected'] as $row) echo "[" . implode(", ", $row) . "]\n";

// Validation Logic
// Expected: First eigenvalue should be large (explained variance), second small.
// Since data is highly linear, dimensionality reduction should preserve most info.
$ev1 = $result['eigenvalues'][0];
$ev2 = $result['eigenvalues'][1] ?? 0;
$ratio = $ev1 / ($ev1 + $ev2);
echo "\nExplained Variance Ratio of PC1: " . number_format($ratio * 100, 2) . "%\n";
