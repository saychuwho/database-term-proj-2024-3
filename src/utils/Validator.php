<?php

class Validator {
    public static function validateSubmission($data) {
        $errors = [];

        if (empty($data['assignment_id'])) {
            $errors[] = 'Assignment ID is required';
        }

        if (empty($data['code'])) {
            $errors[] = 'Code submission is required';
        } elseif (strlen($data['code']) > 65535) {  // Assuming TEXT column in MySQL
            $errors[] = 'Code submission is too long';
        }

        return $errors;
    }

    public static function validateGrading($data) {
        $errors = [];

        if (!isset($data['id'])) {
            $errors[] = 'Submission ID is required';
        }

        if (!isset($data['score']) || !is_numeric($data['score']) || $data['score'] < 0) {
            $errors[] = 'Valid score is required';
        }

        if (empty($data['feedback'])) {
            $errors[] = 'Feedback is required';
        }

        return $errors;
    }
}