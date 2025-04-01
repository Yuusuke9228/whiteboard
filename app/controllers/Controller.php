<?php
// app/controllers/Controller.php
class Controller
{
    protected function view($name, $data = [])
    {
        extract($data);
        require_once __DIR__ . "/../../resources/views/{$name}.php";
    }

    protected function redirect($url)
    {
        header("Location: {$url}");
        exit;
    }

    protected function json($data, $statusCode = 200)
    {
        header('Content-Type: application/json');
        http_response_code($statusCode);
        echo json_encode($data);
        exit;
    }

    protected function validate($data, $rules)
    {
        $errors = [];

        foreach ($rules as $field => $fieldRules) {
            $fieldRules = explode('|', $fieldRules);

            foreach ($fieldRules as $rule) {
                if ($rule === 'required' && (!isset($data[$field]) || empty($data[$field]))) {
                    $errors[$field][] = "{$field} is required";
                } else if (strpos($rule, 'min:') === 0) {
                    $min = explode(':', $rule)[1];
                    if (isset($data[$field]) && strlen($data[$field]) < $min) {
                        $errors[$field][] = "{$field} must be at least {$min} characters";
                    }
                } else if (strpos($rule, 'max:') === 0) {
                    $max = explode(':', $rule)[1];
                    if (isset($data[$field]) && strlen($data[$field]) > $max) {
                        $errors[$field][] = "{$field} must be at most {$max} characters";
                    }
                } else if ($rule === 'email' && isset($data[$field]) && !filter_var($data[$field], FILTER_VALIDATE_EMAIL)) {
                    $errors[$field][] = "{$field} must be a valid email";
                }
            }
        }

        return $errors;
    }
}
