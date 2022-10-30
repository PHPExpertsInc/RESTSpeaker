    <?php declare(strict_types=1);

    use PHPExperts\RESTSpeaker\RESTAuth;
    use PHPExperts\RESTSpeaker\RESTSpeaker;

    require __DIR__ . '/../vendor/autoload.php';

    function viaRESTSpeaker()
    {
        $secret = 'YOUR_SECRET_KEY';
        $action = "submit";

        $auth = new class(RESTAuth::AUTH_NONE) extends RESTAuth
        {
            protected function generateOAuth2TokenOptions(): array
            {
            }

            protected function generatePasskeyOptions(): array
            {
            }
        };

        $api = new RESTSpeaker($auth);
        $response = $api->post('https://www.google.com/recaptcha/api/siteverify', [
            'secret' => $secret,
            'response' => $captcha
        ]);

        if ($response->success == '1') {
            echo "Whoohoo!";
        }
    }

function viaCurl()
{
    $secret = 'YOUR_SECRET_KEY';
    $action = "submit";
// call curl to POST request
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://www.google.com/recaptcha/api/siteverify");
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(array('secret' => $secret, 'response' => $captcha)));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);
    $arrResponse = json_decode($response, true);
// verify the response
    if ($arrResponse["success"] == '1' && $arrResponse["action"] == $action && $arrResponse["score"] >= 0.5) {
// valid submission
// proceed
    } else {
// spam submission
// show error message
    }
}
