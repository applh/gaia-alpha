<?php

namespace MlPca\Service;

class PcaService
{
    /**
     * Fits the model and returns projected data.
     *
     * @param array $data 2D array [row][col] (Observations x Features). Must be numeric.
     * @param int $nComponents Number of components to keep
     * @return array [
     *    'projected' => [],   // Transformed data
     *    'eigenvalues' => [], // Sorted eigenvalues (explained variance)
     *    'eigenvectors' => [] // Top k eigenvectors
     * ]
     * @throws \Exception If data is invalid
     */
    public static function calculate(array $data, int $nComponents): array
    {
        // 1. Validation & Preprocessing
        if (empty($data)) {
            throw new \Exception("Data is empty");
        }

        $rows = count($data);
        $cols = count($data[0]);

        if ($rows < 2) {
            throw new \Exception("Need at least 2 rows of data");
        }

        // Ensure data is numeric
        // Performance note: array_map/is_numeric check might be slow for huge datasets, relying on caller logic or best-effort.

        // --- Step 1: Standardization (Z-Score) ---
        // Calculate Mean
        $means = [];
        for ($j = 0; $j < $cols; $j++) {
            $sum = 0;
            for ($i = 0; $i < $rows; $i++) {
                if (!isset($data[$i][$j]))
                    $data[$i][$j] = 0; // Handle missing data
                $sum += $data[$i][$j];
            }
            $means[$j] = $sum / $rows;
        }

        // Calculate Standard Deviation
        $stdDevs = [];
        for ($j = 0; $j < $cols; $j++) {
            $sumSq = 0;
            for ($i = 0; $i < $rows; $i++) {
                $val = $data[$i][$j] - $means[$j];
                $sumSq += $val * $val;
            }
            $stdDevs[$j] = sqrt($sumSq / ($rows - 1 ?: 1));
        }

        // Normalize
        // To save memory, we can modify $data in place if feasible, but creating a new array is safer.
        // For very large datasets, we should use generators or specialized structures.
        $normalizedData = [];
        for ($i = 0; $i < $rows; $i++) {
            for ($j = 0; $j < $cols; $j++) {
                $normalizedData[$i][$j] = ($stdDevs[$j] != 0)
                    ? ($data[$i][$j] - $means[$j]) / $stdDevs[$j]
                    : 0;
            }
        }

        // --- Step 2: Covariance Matrix ---
        // Cov(X,Y) = E[(X-Mx)(Y-My)]. Since we centered, Cov = (X^T * X) / (N-1)
        // Complexity: O(Col^2 * Rows)
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

        // --- Step 3: Eigendecomposition (Jacobi Algorithm) ---
        // Complexity: O(Col^3) per iteration
        list($eigenvalues, $eigenvectors) = self::jacobiEigenvalueAlgorithm($covarianceMatrix);

        // --- Step 4: Sort & Project ---
        $pairs = [];
        foreach ($eigenvalues as $i => $val) {
            $pairs[] = ['value' => $val, 'vector' => array_column($eigenvectors, $i)];
        }

        // Sort by Eigenvalue Descending
        usort($pairs, fn($a, $b) => $b['value'] <=> $a['value']);

        // Extract Top K Vectors
        $topVectors = [];
        for ($k = 0; $k < $nComponents; $k++) {
            if (isset($pairs[$k])) {
                $topVectors[] = $pairs[$k]['vector'];
            }
        }

        // Project Data
        // Matrix Mult: Normalized (NxM) * Vectors^T (MxK) -> (NxK)
        $projected = [];
        for ($i = 0; $i < $rows; $i++) {
            $newRow = [];
            foreach ($topVectors as $vector) {
                $dot = 0;
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
            // 'covariance' => $covarianceMatrix // Optional debug
        ];
    }

    /**
     * Solves eigenvalues for a real symmetric matrix.
     */
    private static function jacobiEigenvalueAlgorithm(array $A, float $tol = 1e-10, int $maxIter = 100): array
    {
        $n = count($A);
        $V = []; // Identity matrix
        for ($i = 0; $i < $n; $i++) {
            for ($j = 0; $j < $n; $j++)
                $V[$i][$j] = ($i === $j) ? 1.0 : 0.0;
        }

        $iterations = 0;
        while ($iterations < $maxIter) {
            $maxAbs = 0.0;
            $p = 0;
            $q = 0;

            // Find max off-diagonal element
            for ($i = 0; $i < $n; $i++) {
                for ($j = $i + 1; $j < $n; $j++) {
                    if (abs($A[$i][$j]) > $maxAbs) {
                        $maxAbs = abs($A[$i][$j]);
                        $p = $i;
                        $q = $j;
                    }
                }
            }

            if ($maxAbs < $tol)
                break;

            // Rotate
            $theta = 0.5 * atan2(2 * $A[$p][$q], $A[$q][$q] - $A[$p][$p]);
            $c = cos($theta);
            $s = sin($theta);

            // Update A (Diagonal elements)
            $App = $A[$p][$p];
            $Aqq = $A[$q][$q];
            $Apq = $A[$p][$q];
            $A[$p][$p] = $c * $c * $App - 2 * $s * $c * $Apq + $s * $s * $Aqq;
            $A[$q][$q] = $s * $s * $App + 2 * $s * $c * $Apq + $c * $c * $Aqq;
            $A[$p][$q] = 0;
            $A[$q][$p] = 0;

            // Update A (Off-diagonal)
            for ($i = 0; $i < $n; $i++) {
                if ($i != $p && $i != $q) {
                    $Api = $A[$p][$i];
                    $Aqi = $A[$q][$i];
                    $A[$p][$i] = $c * $Api - $s * $Aqi;
                    $A[$i][$p] = $A[$p][$i];
                    $A[$q][$i] = $s * $Api + $c * $Aqi;
                    $A[$i][$q] = $A[$q][$i];
                }
            }

            // Update Eigenvectors
            for ($i = 0; $i < $n; $i++) {
                $Vip = $V[$i][$p];
                $Viq = $V[$i][$q];
                $V[$i][$p] = $c * $Vip - $s * $Viq;
                $V[$i][$q] = $s * $Vip + $c * $Viq;
            }
            $iterations++;
        }

        $evals = [];
        for ($i = 0; $i < $n; $i++)
            $evals[$i] = $A[$i][$i];

        return [$evals, $V];
    }
}
