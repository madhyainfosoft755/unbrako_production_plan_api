<?php

namespace App\Controllers\Api;

use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\Shield\Entities\User;
use CodeIgniter\Shield\Models\UserModel;
use App\Models\CustomUserModel;
use App\Models\ForgotPasswordTokenModel;
use Exception;

use CodeIgniter\Shield\Models\UserIdentityModel;
// use CodeIgniter\Shield\Models\UserIdentityModel;

/**
 * @OA\Info(
 *   title="CodeIgniter v4 APIs Docs with Shield Auth",
 *   version="1.0.0",
 *   description="This documentation will give idea of Register, Login, Profile and Logout APIs"
 * )
 * 
 * @OA\Server(
 *   url="http://localhost:8080/",
 *   description="This is the information of local development server"
 * )
 */

class AuthController extends ResourceController
{
    // protected $modelName = "App\Models\CustomUserModel";
    protected $format = "json";
    // User Register Method [POST] -> username, email, name, password
    /**
     * @OA\Post(
     *    path="/api/register",
     *    summary="User Registration API",
     *    @OA\RequestBody(
     *      required=true,
     *      @OA\JsonContent(
     *         type="object",
     *         @OA\Property(property="username", type="string", example="sanjay_kumar"),
     *         @OA\Property(property="email", type="string", example="sanjay@gmailtest.com"),
     *         @OA\Property(property="name", type="string", example="Sanjay Kumar"),
     *         @OA\Property(property="password", type="string", format="password", example="123456")
     *      )
     *    ),
     *    @OA\Response(
     *       response=200,
     *       description="User Registered Successfully",
     *       @OA\JsonContent(
     *          @OA\Property(property="status", type="boolean", example=true),
     *          @OA\Property(property="message", type="string", example="User Registered Successfully")
     *       )
     *    ),
     *    @OA\Response(
     *       response=400,
     *       description="Invalid Input",
     *       @OA\JsonContent(
     *          @OA\Property(property="status", type="boolean", example=false),
     *          @OA\Property(property="message", type="string", example="Registration Failed due to invalid entries"),
     *          @OA\Property(property="errors", type="object", additionalProperties={"type":"string"})
     *       )
     *    )
     * )
     */

     public function register(){

        // Request Parameters
        $validationRules = [
            "email" => "required",
            "name" => "required",
            "password" => "required",
            "emp_id"=> "required",
            "role"=> "required"
        ];

        if(!$this->validate($validationRules)){

            return $this->respond([
                "status" => false,
                "message" => "Registration Failed due to invalid entries",
                "errors" => $this->validator->getErrors()
            ], 400);
        }

        $modelObject = new CustomUserModel();

        
        $entityObject = new User([
            "name" => $this->request->getVar("name"),
            "email" => $this->request->getVar("email"),
            "email_add" => $this->request->getVar("email"),
            "salutation" => $this->request->getVar("salutation"),
            "emp_id" => $this->request->getVar("emp_id"),
            "password" => $this->request->getVar("password"),
            "role"=> $this->request->getVar("role"),
            "active"=> 1
        ]);

        if($modelObject->save($entityObject)){

            return $this->respond([
                "status" => true,
                "message" => "User Registered Successfully",
            ]);
        } else{

            return $this->respond([
                "status" => false,
                "message" => "Failed to Create User",
            ]);
        }
        
    }

    // User Login API [POST] -> email, password
    /**
     * @OA\Post(
     *    path="/api/login",
     *    summary="User Login API",
     *    @OA\RequestBody(
     *      required=true,
     *      @OA\JsonContent(
     *         type="object",
     *         @OA\Property(property="email", type="string", example="sanjay@gmailtest.com"),
     *         @OA\Property(property="password", type="string", format="password", example="123456")
     *      )
     *    ),
     *    @OA\Response(
     *       response=200,
     *       description="User Logged In Successfully",
     *       @OA\JsonContent(
     *          @OA\Property(property="status", type="boolean", example=true),
     *          @OA\Property(property="message", type="string", example="User logged in"),
     *          @OA\Property(property="token", type="string", example="your_generated_token_here")
     *       )
     *    ),
     *    @OA\Response(
     *       response=400,
     *       description="Invalid Login Credentials",
     *       @OA\JsonContent(
     *          @OA\Property(property="status", type="boolean", example=false),
     *          @OA\Property(property="message", type="string", example="Login Failed")
     *       )
     *    ),
     *    @OA\Response(
     *       response=500,
     *       description="Internal Server Error",
     *       @OA\JsonContent(
     *          @OA\Property(property="status", type="boolean", example=false),
     *          @OA\Property(property="message", type="string", example="An error occurred")
     *       )
     *    )
     * )
     */
    public function login(){

        $validationRules = [
            "email" => "required",
            "password" => "required"
        ];

        if(!$this->validate($validationRules)){

            return $this->respond([
                "status" => false,
                "message" => "Login Failed",
                "errors" => $this->validator->getErrors()
            ]);
        }

        // Check User Details
        $requestedEmail = $this->request->getVar("email");
        $credentials = [
            "email" => $requestedEmail,
            "password" => $this->request->getVar("password")
        ];

        try{

            if(auth()->loggedIn()){

                auth()->logout();
            }

            $loginAttempt = auth()->attempt($credentials);

            if(!$loginAttempt->isOK()){

                return $this->respond([
                    "status" => false,
                    "message" => "Invalid Credentials!"
                ], 400);
            } else{

                if(!auth()->user()->active){
                    return $this->respond([
                        "status" => false,
                        "message" => "Inactive User!"
                    ], 400);
                }

                $userId = auth()->user()->id;

                $shieldModelObject = new UserModel;

                $userInfo = $shieldModelObject->findById($userId);

                $tokenInfo = $userInfo->generateAccessToken("12345678sfgfdgffd");

                $raw_token = $tokenInfo->raw_token;

                return $this->respond([
                    "token" => $raw_token,
                    "id" => auth()->user()->id,
                    "email" => $requestedEmail,
                    "emp_id" => auth()->user()->emp_id,
                    "role" => auth()->user()->role,
                    "name" => auth()->user()->name
                ]);
            }
        } catch (Exception $ex){

            return $this->respond([
                "status" => false,
                "message" => $ex->getMessage()
            ]);
        }
    }

    // User Profile MEthod [GET] -> Protected API Method -> Auth Token Value
    /**
     * @OA\Get(
     *    path="/api/profile",
     *    summary="Get User Profile",
     *    @OA\Response(
     *       response=200,
     *       description="User Profile Information",
     *       @OA\JsonContent(
     *          @OA\Property(property="status", type="boolean", example=true),
     *          @OA\Property(property="message", type="string", example="Profile information"),
     *          @OA\Property(property="data", type="object", additionalProperties={"type":"string"}) 
     *       )
     *    ),
     *    @OA\Response(
     *       response=401,
     *       description="Unauthorized - Invalid or Missing Token",
     *       @OA\JsonContent(
     *          @OA\Property(property="status", type="boolean", example=false),
     *          @OA\Property(property="message", type="string", example="Unauthorized")
     *       )
     *    )
     * )
     */
    public function profile(){

        $userData = auth("tokens")->user();

        return $this->respond([
            "status" => true,
            "message" => "Profile information",
            "data" => $userData
        ]);
    }

    // Logout Method (GET) -> Protected API Method -> Auth Token Value
    /**
     * @OA\Get(
     *    path="/api/logout",
     *    summary="Logout User",
     *    @OA\Response(
     *       response=200,
     *       description="User Logged Out Successfully",
     *       @OA\JsonContent(
     *          @OA\Property(property="status", type="boolean", example=true),
     *          @OA\Property(property="message", type="string", example="User logged out")
     *       )
     *    ),
     *    @OA\Response(
     *       response=401,
     *       description="Unauthorized - Invalid or Missing Token",
     *       @OA\JsonContent(
     *          @OA\Property(property="status", type="boolean", example=false),
     *          @OA\Property(property="message", type="string", example="Unauthorized")
     *       )
     *    )
     * )
     */
    public function logout(){

        auth()->logout();

        auth()->user()->revokeAllAccessTokens();

        return $this->respond([
            "status" => true,
            "message" => "User logged out"
        ]);
    }


    /**
     * @OA\Post(
     *    path="/api/change-password",
     *    summary="Change User Password",
     *    @OA\RequestBody(
     *      required=true,
     *      @OA\JsonContent(
     *         type="object",
     *         @OA\Property(property="current_password", type="string", format="password", example="123456"),
     *         @OA\Property(property="new_password", type="string", format="password", example="newpassword123")
     *      )
     *    ),
     *    @OA\Response(
     *       response=200,
     *       description="Password changed successfully",
     *       @OA\JsonContent(
     *          @OA\Property(property="status", type="boolean", example=true),
     *          @OA\Property(property="message", type="string", example="Password updated successfully")
     *       )
     *    ),
     *    @OA\Response(
     *       response=400,
     *       description="Invalid current password or other errors",
     *       @OA\JsonContent(
     *          @OA\Property(property="status", type="boolean", example=false),
     *          @OA\Property(property="message", type="string", example="Password update failed")
     *       )
     *    )
     * )
     */
    public function changePassword()
    {
        $validationRules = [
            "current_password" => "required",
            "new_password" => "required|min_length[6]"
        ];

        if (!$this->validate($validationRules)) {
            return $this->respond([
                "status" => false,
                "message" => "Password update failed",
                "errors" => $this->validator->getErrors()
            ], 400);
        }

        $currentPassword = $this->request->getVar("current_password");
        $newPassword = $this->request->getVar("new_password");
        $user = auth()->user();
        echo $currentPassword;
        echo '<br>';
        echo $newPassword;

        // print_r(auth()->user());
        // print_r(auth("tokens")->user());
        // print_r(auth()->user()->id);
        // // Assuming $user is the CodeIgniter\Shield\Entities\User object
        // if (!empty($user->currentAccessToken)) {
        //     $secret = $user->currentAccessToken->attributes['secret'];
        //     echo "Secret: " . $secret;
        // } else {
        //     echo "No current access token found.";
        // }
        // echo "amanpass";
        // $identities = new UserIdentityModel();
        // // print_r($identities->getIdentities(auth()->user()));

        // echo password_verify($currentPassword, '$2y$12$RPfrAoAc9LCWprvT9VaJBeh6/lcHQfFMvajJ86p1f6pfR.JtT873S');
        // echo 'ok';
        // Load the database service
        $db = db_connect();
        $builder = $db->table('auth_identities');

        // Get the hashed secret for a user (e.g., by email or user_id)
        $userIdentity = $builder
            ->where('user_id', auth()->user()->id) // Replace 1 with the actual user_id
            ->where('type', 'email_password') // 'password' indicates this is a password identity
            ->get()
            ->getRow();

        if ($userIdentity) {
            $storedHashedPassword = $userIdentity->secret2;
            // echo "Hashed Password (secret): " . $storedHashedPassword;
            $passwordService = service('passwords');
            $isValid = $passwordService->verify($currentPassword, $storedHashedPassword);
    
            if ($isValid) {
                echo "Password is correct!";
                $newPasswordHash = $passwordService->hash($newPassword);
                echo $newPasswordHash;
                // $identities = new UserIdentityModel();
                // $identities->forceMultiplePasswordReset([1,2,3,4]);
                $shieldModelObject = new User();
                echo auth()->user()->getEmail();
                echo auth()->user()->getPasswordHash();  // Correct get password hast method.
                auth()->user()->setPasswordHash($newPasswordHash);

                // $userInfo = $shieldModelObject->getEmail();

                $modelObject = new CustomUserModel();
                // echo $modelObject->getEmail();

                // Save the changes
                if (!$modelObject->save($user)) {
                    return $this->failServerError('Failed to update user.');
                }
            } else {
                echo "Incorrect password!";
            }
        } else {
            echo "User identity not found.";
        }
        // $accessToken = auth("tokens")->getBearerToken();
        // echo $accessToken;
        // if (auth()->loggedIn()) {
        //     // Get the access token
        //     $accessToken = auth("tokens")->getAccessToken();
        //     echo $accessToken;
        // } else {
        //     echo "no access token";
        // }
        // echo auth()->user()->email;
        // print_r(auth()->user()->getPassword());
        // print_r(auth("tokens")->check(["password"=>$currentPassword]));
        // $result = auth()->check([
        //     'token' => 
        //     'email'    => auth()->user()->email,
        //     'password' => $currentPassword,
        // ]);
    
        // if( !$result->isOK() ) {
        //     // Send back the error message
        //     // $error = lang('Auth.errorOldPassword');
        //     echo "not a password";
        // } else{
        //     echo "correct password";
        // }
        die;

        if (!verify($currentPassword, '3b669eeb0e42a8a499d9b5f89958d8490729993a1a41b0fb4133a8d90439b877')) {
            // log_message('debug', 'User object: ' . print_r($user, true));
            log_message('debug', 'Stored password: ' . $user->secret);
            log_message('debug', 'Input password: ' . $currentPassword);
            return $this->respond([
                "status" => false,
                "message" => "Current password is incorrect"
            ], 400);
        }

        $user->password = $newPassword;

        $userModel = new UserModel();
        if ($userModel->save($user)) {
            return $this->respond([
                "status" => true,
                "message" => "Password updated successfully"
            ]);
        }

        return $this->respond([
            "status" => false,
            "message" => "Password update failed"
        ], 400);
    }


    public function changePassword2()
    {
        $validationRules = [
            "current_password" => "required",
            "new_password" => "required|min_length[6]"
        ];

        if (!$this->validate($validationRules)) {
            return $this->respond([
                "status" => false,
                "message" => "Password update failed",
                "errors" => $this->validator->getErrors()
            ], 400);
        }

        $currentPassword = $this->request->getVar("current_password");
        $newPassword = $this->request->getVar("new_password");
        $user = auth()->user();
        $passwordService = service('passwords');

        if (!$passwordService->verify($currentPassword, $user->getPasswordHash())) {
            return $this->respond([
                "status" => false,
                "message" => "Current password is incorrect"
            ], 400);
        }
        $newPasswordHash = $passwordService->hash($newPassword);
        $user->setPasswordHash($newPasswordHash);

        $modelObject = new CustomUserModel();
        if (!$modelObject->save($user)) {
            return $this->respond([
                "status" => false,
                "message" => "Password update failed"
            ], 400);
        }
        
        return $this->respond([
            "status" => true,
            "message" => "Password updated successfully"
        ]);
    }

    public function updateUserDetails(){
        // $user = service('auth')->user(); // Get the authenticated user

        $user = auth()->user();
        if (!$user) {
            return $this->respond(['message' => 'Unauthorized'], 401);
        }

        $data = $this->request->getJSON();

        // Validation rules
        $validation = service('validation');
        $validation->setRules([
            'email'      => 'permit_empty|valid_email|max_length[255]',
            'email_add'  => 'permit_empty|valid_email|max_length[255]',
            'emp_id'     => 'permit_empty|string|max_length[50]',
            'salutation' => 'permit_empty|string|max_length[10]',
            'active'     => 'permit_empty',
        ]);

        if (!$validation->run((array) $data)) {
            return $this->failValidationErrors($validation->getErrors());
        }

        // Update user details
        // $userData = [];

        $modelObject = new CustomUserModel();
        if (isset($data->email)) {
            $user->setEmail($data->email);
            $user->email_add = $data->email;
        }
        if (isset($data->emp_id)) {
            // $userData['emp_id'] = $data->emp_id;
            $user->emp_id = $data->emp_id;
        }
        if (isset($data->name)) {
            // $userData['name'] = $data->name;
            $user->name = $data->name;
        }
        if (isset($data->role)) {
            // $userData['role'] = $data->role;
            $user->role = $data->role;
        }
        if (isset($data->salutation)) {
            // $userData['salutation'] = $data->salutation;
            $user->salutation = $data->salutation;
        }
        if (isset($data->active)) {
            // $userData['active'] = $data->active;
            $user->active = $data->active;
        }

        // Save updates to the database

        // if (!empty($userData)) {
            if (!$modelObject->save($user)) {
                return $this->respond([
                    "status" => false,
                    "message" => "Update failed"
                ], 400);
            }
            // $this->model->update($user->id, $userData);
        // }
    
        return $this->respond([
            'message' => 'User updated successfully.',
            // 'user'    => $userData
        ]);
    }


    /**
     * @OA\Get(
     *    path="/api/users",
     *    summary="Get All Users",
     *    @OA\Response(
     *       response=200,
     *       description="List of all users",
     *       @OA\JsonContent(
     *          @OA\Property(property="status", type="boolean", example=true),
     *          @OA\Property(property="message", type="string", example="Users retrieved successfully"),
     *          @OA\Property(property="data", type="array", @OA\Items(type="object"))
     *       )
     *    ),
     *    @OA\Response(
     *       response=403,
     *       description="Unauthorized - Admin access required",
     *       @OA\JsonContent(
     *          @OA\Property(property="status", type="boolean", example=false),
     *          @OA\Property(property="message", type="string", example="Forbidden")
     *       )
     *    )
     * )
     */
    public function getAllUsers()
    {

        $userModel = new UserModel();
        $users = $userModel->findAll();

        return $this->respond([
            "status" => true,
            "message" => "Users retrieved successfully",
            "data" => $users
        ]);
    }

    public function forgotPassword(){
        helper('my_email.php');
        // Request Parameters
        $validationRules = [
            "email" => "required"
        ];

        if(!$this->validate($validationRules)){

            return $this->respond([
                "status" => false,
                "errors" => $this->validator->getErrors()
            ], 400);
        }
        // reset_password_link
        $to = $this->request->getVar("email");
        $subject = 'Reset Password Link';
        $view = 'reset_password';
        
        $data = [
            'title' => 'Welcome Email',
            'heading' => 'Welcome to Our Platform!',
            // 'reset_password_link' => 'Thank you for signing up with us. We are excited to have you onboard.',
        ];
        // $options = [
        //     'fromEmail' => 'support@example.com',
        //     'fromName' => 'Support Team',
        // ];
        $options = [];

        // $result = sendAppEmail($to, $subject, $view, $data, $options);
        $modelObject = new CustomUserModel();
        $requestedUser = $modelObject->findByCredentials(['email'=>$to]);
        // print_r($modelObject->findByCredentials(['email'=>$to]));

        if(!$requestedUser){
            return $this->respond([
                "status" => false,
                "message" => "Email not found"
            ], 400);
        }

        $db = db_connect();
        $builder = $db->table('forgot_password_token');

        // Example data
        $data_for_token = json_encode(['id' => $requestedUser->id, 'timestamp' => time()]); // Data to be encrypted

        // Encrypt the data
        $encryptedData = encryptData($data_for_token);

        // $encryptedData = generateRandomString();

        $inserted = $builder->insert([
            "user"=>$requestedUser->id,
            "token"=>$encryptedData
        ]);

        if ($inserted) {
            // Identity created successfully.
            // Retrieve the inserted ID
            $insertedId = $db->insertID();
            $data['reset_password_link'] = env('FRONTENDRESETPASSWORDURL') . '/'.$encryptedData;
            $result = sendAppEmail($to, $subject, $view, $data, $options);
            if ($result['success']) {
                $updateData = [
                    'email_send' => 1, // Set the identity as verified
                ];
    
                // You can update the row using the 'user_id' or 'secret' (the email in this case)
                $update = $builder->set($updateData)
                                  ->where('id',$insertedId) // Can also use 'user_id'
                                  ->update();
                return $this->response->setJSON(['status' => 'success', 'message' => $result['message']]);
            } else {
                return $this->response->setJSON(['status' => 'error', 'message' => $result['message']]);
            }
        }
        return $this->respond([
            "status" => false,
            "message" => "Something went wrong. Please try after some time."
        ], 500);

        

            

    }

    public function checkResetPasswordToken(){
        $validationRules = [
            "token" => "required|string"
        ];
        
        if (!$this->validate($validationRules)) {
            return $this->respond([
                "status" => false,
                "message" => "Validation failed",
                "errors" => $this->validator->getErrors()
            ], 400);
        }
        $token = $this->request->getVar('token');

        $forgotPasswordModel = new ForgotPasswordTokenModel();
        $forgotPasswordRecord = $forgotPasswordModel->where('token', $token)
        ->orderBy('created_at', 'DESC')
        ->first();
        
        // If no record is found, return an error response
        if (!$forgotPasswordRecord) {
            return $this->response->setStatusCode(ResponseInterface::HTTP_NOT_FOUND)
            ->setJSON(['error' => 'Token not found', 'message'=> 'Token could not be found!']);
        }

        // Check if the token is expired (30 minutes validation)
        $createdAt = strtotime($forgotPasswordRecord['created_at']);
        $currentTime = time();
        $timeDifference = $currentTime - $createdAt;

        if ($timeDifference > 1800) { // 30 minutes = 1800 seconds
            return $this->response->setStatusCode(ResponseInterface::HTTP_BAD_REQUEST)
            ->setJSON(['error' => 'Token expired', 'message'=> 'Token expired!']);
        }

        // Check if reset is already complete
        if ($forgotPasswordRecord['reset_complete'] == 1) {
            return $this->response->setStatusCode(ResponseInterface::HTTP_BAD_REQUEST)
            ->setJSON(['error' => 'Password already reset']);
        }
        return $this->respond([
            "status" => true,
            "message" => "Token Found!"
        ], 200);
    }


    public function resetPassword()
    {
        $validationRules = [
            "token" => "required|string",
            "new_password" => "required|min_length[6]",
            "confirm_password" => "required|matches[new_password]"
        ];
        
        if (!$this->validate($validationRules)) {
            return $this->respond([
                "status" => false,
                "message" => "Validation failed",
                "errors" => $this->validator->getErrors()
            ], 400);
        }
        $token = $this->request->getVar('token');
        
        // Get the ForgotPasswordTokenModel
        $forgotPasswordModel = new ForgotPasswordTokenModel();
        // Fetch the record for the provided token
        
        $forgotPasswordRecord = $forgotPasswordModel->where('token', $token)
                                                   ->orderBy('created_at', 'DESC')
                                                   ->first();
                                                   
        // If no record is found, return an error response
        if (!$forgotPasswordRecord) {
            return $this->response->setStatusCode(ResponseInterface::HTTP_NOT_FOUND)
                                   ->setJSON(['error' => 'Token not found']);
        }
        
        // Check if the token is expired (30 minutes validation)
        $createdAt = strtotime($forgotPasswordRecord['created_at']);
        $currentTime = time();
        $timeDifference = $currentTime - $createdAt;

        if ($timeDifference > 1800) { // 30 minutes = 1800 seconds
            return $this->response->setStatusCode(ResponseInterface::HTTP_BAD_REQUEST)
                                   ->setJSON(['error' => 'Token expired']);
        }
        // print_r([
        //     'time_diff'=>$timeDifference,
        //     'curre_time'=>$currentTime,
        //     "created_at"=>$createdAt
        // ]);
        
        // Check if reset is already complete
        if ($forgotPasswordRecord['reset_complete'] == 1) {
            return $this->response->setStatusCode(ResponseInterface::HTTP_BAD_REQUEST)
                                   ->setJSON(['error' => 'Password already reset']);
        }
        
        // Validate incoming password (optional: You can validate here if password is strong enough)
        $newPassword = $this->request->getVar('new_password');
        if (!$newPassword || strlen($newPassword) < 6) {
            return $this->response->setStatusCode(ResponseInterface::HTTP_BAD_REQUEST)
                                   ->setJSON(['error' => 'Invalid password']);
        }

        // Get the user ID associated with this token
        $userId = $forgotPasswordRecord['user'];
        // echo $token ; 
        // print_r($forgotPasswordRecord);
        // die;
        // Update the user's password
        $shieldModelObject = new UserModel;

        $user = $shieldModelObject->findById($userId);

        if ($user) {
            // Encrypt the new password (you can use the password_hash function)
            $passwordService = service('passwords');
            $newPasswordHash = $passwordService->hash($newPassword);
            $user->setPasswordHash($newPasswordHash);

            $modelObject = new CustomUserModel();
            if (!$modelObject->save($user)) {
                return $this->respond([
                    "status" => false,
                    "message" => "Password update failed"
                ], 400);
            }

            // Mark the reset as complete
            $forgotPasswordModel->update($forgotPasswordRecord['id'], [
                'reset_complete' => true,
                'reset_completed_at' =>date("Y-m-d h:i:sa") 
            ]);
            
            // return $this->respond([
            //     "status" => true,
            //     "message" => "Password reset successfully"
            // ]);
            // $user->update($userId, [
                //     'password' => password_hash($newPassword, PASSWORD_BCRYPT),
                // ]);
            return $this->response->setStatusCode(ResponseInterface::HTTP_OK)
                                    ->setJSON(['message' => 'Password reset successful']);

            

            // Send a response
        } else {
            return $this->response->setStatusCode(ResponseInterface::HTTP_NOT_FOUND)
                                   ->setJSON(['error' => 'User not found']);
        }
    }


}