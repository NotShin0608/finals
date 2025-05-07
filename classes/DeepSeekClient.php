<?php
class DeepSeekClient {
    private $api_key;
    private $api_url;
    private $site_url;
    private $site_name;
    
    public function __construct() {
        $this->api_key = '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';
        $this->api_url = 'https://openrouter.ai/api/v1/chat/completions';
        $this->site_url = 'https://localhost';
        $this->site_name = 'Budget Forecast System';
    }

    public function generateForecast($historicalData, $selectedYear) {
        try {
            $prompt = "You are a financial AI assistant. Based on this historical budget data: " . 
                     json_encode($historicalData) . 
                     "\n\nGenerate a monthly budget forecast for year $selectedYear. " .
                     "Consider historical trends, seasonal patterns, and growth rates. " .
                     "Return the forecast in this JSON format exactly: " .
                     "{\n" .
                     "  \"months\": [\"Jan\", \"Feb\", \"Mar\", \"Apr\", \"May\", \"Jun\", \"Jul\", \"Aug\", \"Sep\", \"Oct\", \"Nov\", \"Dec\"],\n" .
                     "  \"forecast\": [numbers...],\n" .
                     "  \"lower_bound\": [numbers...],\n" .
                     "  \"upper_bound\": [numbers...],\n" .
                     "  \"actual\": [null, null, ...]\n" .
                     "}";

            $messages = [
                [
                    "role" => "user",
                    "content" => $prompt
                ]
            ];

            $response = $this->makeApiCall($messages);
            
            if (isset($response['choices'][0]['message']['content'])) {
                $aiResponse = json_decode($response['choices'][0]['message']['content'], true);
                
                if ($this->isValidForecastFormat($aiResponse)) {
                    return $aiResponse;
                }
            }
            
            return $this->generateTraditionalForecast($historicalData, $selectedYear);
            
        } catch (Exception $e) {
            error_log("AI Forecast Error: " . $e->getMessage());
            return $this->generateTraditionalForecast($historicalData, $selectedYear);
        }
    }

    private function generateTraditionalForecast($historicalData, $selectedYear) {
        $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        $forecast = [];
        $lowerBound = [];
        $upperBound = [];
        $actual = array_fill(0, 12, null);

        // Calculate monthly averages and growth rate
        $monthlyAverages = [];
        $yearlyTotals = [];
        
        foreach ($historicalData['monthly'] as $data) {
            $month = (int)$data['month'] - 1; // Convert to 0-based index
            $year = (int)$data['year'];
            
            if (!isset($monthlyAverages[$month])) {
                $monthlyAverages[$month] = [];
            }
            $monthlyAverages[$month][] = $data['total_amount'];
            
            if (!isset($yearlyTotals[$year])) {
                $yearlyTotals[$year] = 0;
            }
            $yearlyTotals[$year] += $data['total_amount'];
        }

        // Calculate growth rate
        $yearlyGrowthRate = 0.05; // Default 5%
        $years = array_keys($yearlyTotals);
        if (count($years) >= 2) {
            sort($years);
            $lastYear = end($years);
            $previousYear = prev($years);
            if ($yearlyTotals[$previousYear] > 0) {
                $yearlyGrowthRate = ($yearlyTotals[$lastYear] - $yearlyTotals[$previousYear]) / $yearlyTotals[$previousYear];
            }
        }

        // Generate forecast for each month
        $currentYear = date('Y');
        $yearDiff = $selectedYear - $currentYear;
        $cumulativeGrowth = pow(1 + $yearlyGrowthRate, $yearDiff);

        for ($i = 0; $i < 12; $i++) {
            if (!empty($monthlyAverages[$i])) {
                $baseAmount = array_sum($monthlyAverages[$i]) / count($monthlyAverages[$i]);
                $forecastAmount = $baseAmount * $cumulativeGrowth;
                
                // Add seasonal adjustment
                $seasonalFactor = 1 + (sin(($i + 1) * pi() / 6) * 0.1);
                $forecastAmount *= $seasonalFactor;
                
                $forecast[$i] = round($forecastAmount, 2);
                $lowerBound[$i] = round($forecastAmount * 0.9, 2);
                $upperBound[$i] = round($forecastAmount * 1.1, 2);
            } else {
                $avgAmount = array_sum(array_map('array_sum', $monthlyAverages)) / 
                           array_sum(array_map('count', $monthlyAverages));
                $forecast[$i] = round($avgAmount * $cumulativeGrowth, 2);
                $lowerBound[$i] = round($forecast[$i] * 0.9, 2);
                $upperBound[$i] = round($forecast[$i] * 1.1, 2);
            }
        }

        return [
            'months' => $months,
            'forecast' => $forecast,
            'lower_bound' => $lowerBound,
            'upper_bound' => $upperBound,
            'actual' => $actual
        ];
    }

    public function generateBudgetAnalysis($historicalData, $selectedYear) {
        try {
            $prompt = "As a financial analyst AI, analyze this budget data: " . json_encode($historicalData) . 
                     "\n\nProvide a brief but detailed analysis for year $selectedYear that includes:" .
                     "\n1. Overall trend analysis (increasing/decreasing/stable)" .
                     "\n2. Significant patterns or anomalies" .
                     "\n3. Performance insights" .
                     "\n4. Future outlook" .
                     "\n\nFormat your response as a detailed paragraph that a financial manager would find useful." .
                     "\nInclude specific numbers and percentages where relevant." .
                     "\nMake sure to mention any concerning patterns or positive developments.";

            $messages = [
                [
                    "role" => "user",
                    "content" => $prompt
                ]
            ];

            $response = $this->makeApiCall($messages);
            
            if (isset($response['choices'][0]['message']['content'])) {
                return [
                    'feedback' => $response['choices'][0]['message']['content']
                ];
            }
            
            return $this->generateDefaultAnalysis($historicalData, $selectedYear);
            
        } catch (Exception $e) {
            error_log("AI Analysis Error: " . $e->getMessage());
            return $this->generateDefaultAnalysis($historicalData, $selectedYear);
        }
    }

    private function generateDefaultAnalysis($historicalData, $selectedYear) {
        $totalAmount = 0;
        $monthCount = 0;
        $trend = 'stable';
        $growth = 0;
        
        if (!empty($historicalData['monthly'])) {
            $amounts = array_column($historicalData['monthly'], 'total_amount');
            $totalAmount = array_sum($amounts);
            $monthCount = count($amounts);
            
            if ($monthCount > 1) {
                $firstHalf = array_slice($amounts, 0, floor($monthCount/2));
                $secondHalf = array_slice($amounts, floor($monthCount/2));
                $firstAvg = array_sum($firstHalf) / count($firstHalf);
                $secondAvg = array_sum($secondHalf) / count($secondHalf);
                $growth = (($secondAvg - $firstAvg) / $firstAvg) * 100;
                
                if ($growth > 5) {
                    $trend = 'increasing';
                } elseif ($growth < -5) {
                    $trend = 'decreasing';
                }
            }
        }

        $avgMonthly = $monthCount > 0 ? $totalAmount / $monthCount : 0;

        return [
            'feedback' => sprintf(
                "Budget Analysis for %d:\n\n" .
                "The overall budget trend appears to be %s, with a %.1f%% change observed over the analysis period. " .
                "Average monthly expenditure is PHP %s. Based on historical patterns, we recommend close monitoring " .
                "of budget allocation and spending patterns. The forecast suggests potential variations in monthly " .
                "expenses that should be considered in planning.",
                $selectedYear,
                $trend,
                $growth,
                number_format($avgMonthly, 2)
            )
        ];
    }

    private function makeApiCall($messages) {
        $ch = curl_init($this->api_url);
        
        $headers = [
            'Authorization: Bearer ' . $this->api_key,
            'HTTP-Referer: ' . $this->site_url,
            'X-Title: ' . $this->site_name,
            'Content-Type: application/json'
        ];
        
        $data = [
            'model' => 'deepseek/deepseek-prover-v2:free',
            'messages' => $messages
        ];
        
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_TIMEOUT => 30
        ]);
        
        $response = curl_exec($ch);
        $error = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        curl_close($ch);
        
        if ($error) {
            throw new Exception("API Call Error: $error");
        }
        
        if ($httpCode !== 200) {
            throw new Exception("API Error: HTTP Code $httpCode");
        }
        
        return json_decode($response, true);
    }

    private function isValidForecastFormat($data) {
        return isset($data['months']) && 
               isset($data['forecast']) && 
               isset($data['lower_bound']) && 
               isset($data['upper_bound']) &&
               isset($data['actual']) &&
               is_array($data['months']) &&
               is_array($data['forecast']) &&
               is_array($data['lower_bound']) &&
               is_array($data['upper_bound']) &&
               is_array($data['actual']) &&
               count($data['months']) === 12 &&
               count($data['forecast']) === 12 &&
               count($data['lower_bound']) === 12 &&
               count($data['upper_bound']) === 12 &&
               count($data['actual']) === 12;
    }
}