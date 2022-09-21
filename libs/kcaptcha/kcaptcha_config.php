<?php

# KCAPTCHA configuration file

$alphabet = "0123456789abcdefghijklmnopqrstuvwxyz";

# symbols used to draw CAPTCHA
$allowed_symbols = "23456789abcdeghkmnpqsuvxyz";

# folder with fonts
$fontsdir = RL_LIBS . 'fonts';

# CAPTCHA string length
$length = $rlConfig->getConfig('security_code_length');

if ($length > 10)
{
    $length = 10;
}

# CAPTCHA image size
$width  = 110;
$height = 55;

# symbol's vertical fluctuation amplitude divided by 2
$fluctuation_amplitude = 10;

# increase safety by prevention of spaces between symbols
$no_spaces = true;

# show credits
$show_credits = false;

# CAPTCHA image colors (RGB, 0-255)
$foreground_color = array(mt_rand(50,100), mt_rand(50,100), mt_rand(50,100));
$background_color = array(mt_rand(180,255), mt_rand(180,255), mt_rand(180,255));

# JPEG quality of CAPTCHA image
$jpeg_quality = 90;

?>
