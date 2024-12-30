<?php

// namespace App\Helpers;

use CodeIgniter\CodeIgniter;
// use CodeIgniter\Email\Email;

/**
 * Send an email using CodeIgniter's email library.
 *
 * @param string $to       Recipient email address
 * @param string $subject  Subject of the email
 * @param string $view     View file for email content
 * @param array  $data     Data for the view
 * @param array  $options  Additional options (e.g., attachments, CC, BCC)
 *
 * @return array Returns an array with 'success' (bool) and 'message' (string)
 */
function sendAppEmail(string $to, string $subject, string $view, array $data = [], array $options = [])
{
    $email = \Config\Services::email();

    // Load email configuration from .env
    $config = [
        'protocol' => getenv('email.protocol'),
        'SMTPHost' => getenv('email.SMTPHost'),
        'SMTPUser' => getenv('email.SMTPUser'),
        'SMTPPass' => getenv('email.SMTPPass'),
        'SMTPPort' => (int)getenv('email.SMTPPort'),
        // 'SMTPCrypto' => getenv('email.SMTPCrypto'),
        'mailType' => 'html',
        'charset' => 'utf-8',
        'wordWrap' => true,
        'newline'   => "\r\n",
    ];

    $email->initialize($config);

    // Set email parameters
    $email->setTo($to);
    $email->setSubject($subject);

    // Load the email content from the view
    $emailContent = view($view, $data);
    $email->setMessage($emailContent);

    // Set sender details
    $fromEmail = $options['fromEmail'] ?? getenv('email.fromEmail');
    $fromName = $options['fromName'] ?? getenv('email.fromName');
    $email->setFrom($fromEmail, $fromName);

    // Add CC, BCC, or attachments if provided
    if (!empty($options['cc'])) {
        $email->setCC($options['cc']);
    }
    if (!empty($options['bcc'])) {
        $email->setBCC($options['bcc']);
    }
    if (!empty($options['attachments'])) {
        foreach ($options['attachments'] as $attachment) {
            $email->attach($attachment);
        }
    }

    // Send the email and return the result
    if ($email->send()) {
        return ['success' => true, 'message' => 'Email sent successfully'];
    } else {
        return ['success' => false, 'message' => $email->printDebugger(['headers'])];
    }
}

// Function to encrypt data
// function encryptData($data)
// {
//     // $resetPasswordTokenSecretKey = 'b6b90359a2549a22589520e8a46a60d380bc0024937eed6dd3a6b8a7cebc9fe9';
//     $secretKey = getenv('resetPasswordTokenSecretKey');
//     $method = 'AES-256-CBC'; // You can use other methods like AES-128-CBC
//     $iv = openssl_random_pseudo_bytes(16); // Generate a random initialization vector (IV)
//     $encrypted = openssl_encrypt($data, $method, $secretKey, 0, $iv); // Encrypt the data
//     // Return both the encrypted data and the IV (Initialization Vector) for decryption
//     return base64_encode($encrypted . '::' . $iv); // Concatenate encrypted data and IV, then encode it in base64
    
// }

// // Function to decrypt data
// function decryptData($encryptedData)
// {
//     $secretKey = getenv('resetPasswordTokenSecretKey');
//     $method = 'AES-256-CBC'; // Same method used for encryption
//     list($encrypted, $iv) = explode('::', base64_decode($encryptedData), 2); // Decode and separate encrypted data and IV
//     return openssl_decrypt($encrypted, $method, $secretKey, 0, $iv); // Decrypt the data using the secret key and IV
// }

function encryptData($data) {
    $secretKey = getenv('resetPasswordTokenSecretKey');
    $method = 'AES-256-CBC';
    $iv = openssl_random_pseudo_bytes(16); // Generate a random initialization vector (IV)
    $encrypted = openssl_encrypt($data, $method, $secretKey, 0, $iv); // Encrypt the data
    
    // Concatenate encrypted data and IV
    $encryptedData = $encrypted . '::' . base64_encode($iv);
    
    // Use URL-safe base64 encoding
    return strtr(base64_encode($encryptedData), '+/=', '-_,');
}

function decryptData($encodedData) {
    $secretKey = getenv('resetPasswordTokenSecretKey');
    $method = 'AES-256-CBC';
    
    // Reverse URL-safe encoding
    $base64Decoded = base64_decode(strtr($encodedData, '-_,', '+/='));
    
    // Split the encrypted data and IV
    list($encryptedData, $iv) = explode('::', $base64Decoded);
    
    // Decode IV
    $iv = base64_decode($iv);
    
    // Decrypt the data
    return openssl_decrypt($encryptedData, $method, $secretKey, 0, $iv);
}


function generateRandomString($length = 90) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[random_int(0, $charactersLength - 1)];
    }
    return $randomString;
}


// namespace App\Helpers;

// use CodeIgniter\Email\Email;

// /**
//  * Send an email using CodeIgniter's email library.
//  *
//  * @param string $to       Recipient email address
//  * @param string $subject  Subject of the email
//  * @param string $message  Email content
//  * @param array  $options  Additional options such as attachments
//  *
//  * @return bool|string Returns true if email is sent, otherwise error message
//  */
// function sendEmail(string $to, string $subject, string $message, array $options = [])
// {
//     $email = \Config\Services::email();

//     // Load email configuration
//     $config = config('Email');
//     $email->initialize($config);

//     // Set email parameters
//     $email->setTo($to);
//     $email->setSubject($subject);
//     $message = view("emails/$template", ["name"=> $data]);
//     $email->setMessage($message);

//     // Set sender details
//     $fromEmail = $options['fromEmail'] ?? $config->fromEmail;
//     $fromName = $options['fromName'] ?? $config->fromName;
//     $email->setFrom($fromEmail, $fromName);

//     // Add CC, BCC, or attachments if provided
//     if (!empty($options['cc'])) {
//         $email->setCC($options['cc']);
//     }
//     if (!empty($options['bcc'])) {
//         $email->setBCC($options['bcc']);
//     }
//     if (!empty($options['attachments'])) {
//         foreach ($options['attachments'] as $attachment) {
//             $email->attach($attachment);
//         }
//     }

//     // Send the email and handle the result
//     if ($email->send()) {
//         return true;
//     } else {
//         return $email->printDebugger(['headers']);
//     }
// }


// $to = $this->request->getVar('to');
//         $subject = $this->request->getVar('subject');
//         $template = $this->request->getVar('template');
//         $data = $this->request->getVar('data'); // Template variables

//         if (!$to || !$subject || !$template) {
//             return $this->fail('Missing required fields');
//         }

//         $email = $this->email;

//         $message = view("emails/$template", ["name"=> $data]);

//         $email->setTo($to);
//         $email->setSubject($subject);
//         $email->setMessage($message);

//         if ($email->send()) {
//             return $this->respond(['message' => 'Email sent successfully']);
//         }

//         return $this->fail('Failed to send email');