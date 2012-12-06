<?php

function step($text, $toplevel=false) {
    global $step;
    if ($toplevel) {
        print "<br><b>{$step}. {$text}</b><br>";
        $step++;
    } else {
        print "&nbsp;&nbsp;&nbsp;&nbsp; &middot; {$text}<br>";
    }
} $step = 1;

function pre($var) {
    print "<pre>";
    var_dump($var);
    print "</pre>";
}

function call($url, $post_params=array()) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    // Cookie management
    curl_setopt($ch, CURLOPT_COOKIEJAR, '/tmp/cookies.txt');
    curl_setopt($ch, CURLOPT_COOKIEFILE, '/tmp/cookies.txt');
    // POST method and params (if applicable)
    curl_setopt($ch, CURLOPT_POST, count($post_params));
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_params));
    // Return configuration
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_HEADER, TRUE);
    //curl_setopt($ch, CURLOPT_NOBODY, TRUE); // remove body
    $r = curl_exec($ch);
    if ($r === false) pre(curl_error($ch)); //throw new Exception(curl_error($ch));
    return $r;
}

function getheader($header_name, $call_return) {
    preg_match("/{$header_name}: (.*)/", $call_return, $m);
    return @$m[1];
}

function getformaction($html) {
    preg_match('/<form.+action="(.*?)"/', $html, $m);
    // Replaces '&amp;' with '&'
    return str_replace('&amp;', '&', @$m[1]);
}

// Note: cookies are managed by php-curl-module,
//       only the last cookie (associated with the target url) must be stored
// Config
$url = 'https://wwwfbm.unil.ch/iafbm/';

// Steps
step("Call a target url (shibboleth-protected)", true);
$s1 = call($url);
$s1_redirect_url = getheader('Location', $s1);
step("Extract the redirect url: {$s1_redirect_url}");

step("Call the redirect url", true);
$s2 = call($s1_redirect_url);
$s2_form_action_url = getformaction($s2);
step("Extract the form action url: {$s2_form_action_url}");
$s1_baseurl = parse_url($s1_redirect_url);
$s2_post_url = "{$s1_baseurl['scheme']}://{$s1_baseurl['host']}{$s2_form_action_url}"; // FIXME: one might use $s1_redirect_url directly (they are the same) and spare this step
step("Create full url: {$s2_post_url}");

step("Call the form action url with POST params", true);
$s3_post_params = array('user_idp' => 'https://aai.unil.ch/idp/shibboleth'); // Select local authority (fetch it from switch-aai org selection page dropdown)
$s3 = call($s2_post_url, $s3_post_params);
$s3_redirect_url = getheader('Location', $s3);
step("Extract the redirect url: {$s3_redirect_url}");

step("Call the redirect url", true);
$s4 = call($s3_redirect_url);
$s4_redirect_url = getheader('Location', $s4);
step("Extract the redirect url: {$s4_redirect_url}");

step("Call the redirect url", true);
$s5 = call($s4_redirect_url);
$s5_redirect_url = getheader('Location', $s5);
step("Extract the redirect url: {$s5_redirect_url}");

step("Call the redirect url", true);
$s6 = call($s5_redirect_url);
$s6_redirect_url = getheader('Location', $s6);
step("Extract the redirect url: {$s6_redirect_url}");

// org-specific from processing
step("Call the redirect url", true);
$s7 = call($s6_redirect_url);
$s7_form_action_url = getformaction($s7);
step("Extract the form action url: {$s7_form_action_url}");

step("Call the form action url with POST params", true);
$s8_post_params = array(
    'j_username' => 'your-username',
    'j_password' => 'your-password'
);
$s8 = call($s7_form_action_url, $s8_post_params);

pre($s8);

// ... ...
exit();
step("Call the target url with shib cookie (_shibsession_*)", true);

curl_close($ch);
