<?php
error_log("Grade submission process started");
require_once 'C:/xampp/htdocs/OOP/src/config/database.php';
require_once 'C:/xampp/htdocs/OOP/src/models/Submission.php';
require_once 'C:/xampp/htdocs/OOP/src/models/Assignment.php';

$config = require 'C:/xampp/htdocs/OOP/src/config/database.php';
$dsn = "mysql:host={$config['host']};dbname={$config['dbname']};port={$config['port']}";
$pdo = new PDO($dsn, $config['user'], $config['password']);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$submission = new Submission($pdo);
$assignment = new Assignment($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $submissionId = $data['id'];
    
    error_log("Submission ID: " . $submissionId);
    
    // 제출된 코드와 관련 정보 가져오기
    $submissionData = $submission->getById($submissionId);
    $assignmentData = $assignment->getById($submissionData['assignment_id']);
    $testCases = $assignment->getTestCases($submissionData['assignment_id'], true);

    error_log("Submission data: " . print_r($submissionData, true));
    error_log("Assignment data: " . print_r($assignmentData, true));
    error_log("Test cases: " . print_r($testCases, true));
    
    // C++ 코드를 파일로 저장
    $filename = 'submission_' . $submissionId . '.cpp';
    file_put_contents($filename, $submissionData['code']);
    error_log("Code saved to file: " . $filename);
    
    // 컴파일
    exec("g++ $filename -o submission_$submissionId.exe 2>&1", $compileOutput, $compileReturnVar);
    error_log("Compilation output: " . implode("\n", $compileOutput));
    error_log("Compilation return var: " . $compileReturnVar);
    
    if ($compileReturnVar !== 0) {
        // 컴파일 에러
        $feedback = "Compilation error: " . implode("\n", $compileOutput);
        $score = 0;
        error_log("Compilation error encountered");
    } else {
        // 테스트 케이스 실행
        $passedTests = 0;
        $totalTests = count($testCases);
        $feedback = "";
        
        foreach ($testCases as $testCase) {
            $input = trim($testCase['input']);
            $expectedOutput = trim($testCase['expected_output']);
            
            error_log("Test case input: " . $input);
            error_log("Expected output: '" . $expectedOutput . "'");
            
            // 프로그램 실행 및 출력 캡처
            $output = [];
            exec("echo $input | submission_$submissionId.exe", $output, $returnVar);
            $actualOutput = trim(implode("\n", $output));
            
            error_log("Actual output: '" . $actualOutput . "'");
            error_log("Return var: " . $returnVar);
            
            if ($actualOutput === $expectedOutput) {
                $passedTests++;
            } else {
                if (!$testCase['is_secret']) {
                    $feedback .= "Test case failed.\nInput: $input\nExpected: $expectedOutput\nGot: $actualOutput\n\n";
                } else {
                    $feedback .= "Secret test case failed.\n";
                }
            }
        }
        
        $score = ($passedTests / $totalTests) * $assignmentData['max_score'];
        $feedback = "Passed $passedTests out of $totalTests test cases.\n\n" . $feedback;
        error_log("Score calculated: " . $score);
    }
    
    // 임시 파일 삭제
    unlink($filename);
    if (file_exists("submission_$submissionId.exe")) {
        unlink("submission_$submissionId.exe");
    }
    
    // 점수와 피드백 업데이트
    $submission->updateGrade($submissionId, $score, $feedback);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['score' => $score, 'feedback' => $feedback]);
    
    error_log("Grade updated successfully for submission ID: " . $submissionId);
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    error_log("Invalid request method");
}
?>