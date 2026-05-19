function generate_csrf_token()
{
    if (empty($_SESSION['csrf_token'])) {
        try {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        } catch (Exception $e) {
            // fallback
            $_SESSION['csrf_token'] = bin2hex(md5(uniqid('', true)));
        }
    }
    return $_SESSION['csrf_token'];
}

function get_csrf_token_json()
{
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['token' => generate_csrf_token()]);
    exit;
}

function validate_csrf_token($token)
{
    if (empty($_SESSION['csrf_token']) || empty($token)) return false;
    return hash_equals($_SESSION['csrf_token'], $token);
}

// if requested for a token return it as a json response
if (isset($_GET['token'])) {
    get_csrf_token_json();
}

// helper to extract token from JSON requests (X header) or POST
function extract_request_csrf()
{
    // check X-CSRF-Token header first, since that's the most likely place for it to be in a well-structured request.
    // this also allows us to avoid parsing the body if we don't have to, which is a nice bonus.
    if (!empty($_SERVER['HTTP_X_CSRF_TOKEN'])) {
        return $_SERVER['HTTP_X_CSRF_TOKEN'];
    }

    $headers = function_exists('getallheaders') ? getallheaders() : [];
    if (!empty($headers['X-CSRF-Token'])) return $headers['X-CSRF-Token'];

    if (!empty($_POST['csrf_token'])) return $_POST['csrf_token'];

    $raw = file_get_contents('php://input');
    if ($raw) {
        $decoded = json_decode($raw, true);
        if (is_array($decoded) && !empty($decoded['csrf_token'])) return $decoded['csrf_token'];
    }

    return null;
}
