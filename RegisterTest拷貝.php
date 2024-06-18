<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\UsersModel;
use App\Services\RegisterService;
use App\Repositories\CacheRepository;
/**
* @group Feature
*/
     

class RegisterTest extends TestCase
{
    
    use RefreshDatabase;
    
    public function setUp(): void
    {
        parent::setUp();
        
        // 預設假資料
        UsersModel::create([
            'type' => 'personal',
            'uuid' => 'test',
            'account' => 'harry+1@mail.8899.com.tw',
            'password' => '1234556'
        ]);
        
        config([
            'bstyle.email.resend_seconds' => 3,
            'bstyle.email.expires_seconds' => 3,
            'bstyle.final.expires_seconds' => 3,
            'bstyle.ip.limit_count' => 100
        ]);
    }
    
    //驗證信發送成功
    public function test_send_register_mail_successfully(): void
    {
        $response = $this->post('/api/register/email',[
                'account' => 'harry@mail.8899.com.tw'
            ]
        );
    
        $this->assertTrue($response['status']);
    }
    
    //驗證信參數格式錯誤
    public function test_send_register_mail_invalid(): void
    {
        //參數空值 START
        $response = $this->post('/api/register/email',[
                'account' => ''
            ]
        );
    
        $this->assertFalse($response['status']);
        //參數空值 END
        
        //信箱格式錯誤 START
        $response = $this->post('/api/register/email',[
                'account' => 'harry'
            ]
        );
    
        $this->assertFalse($response['status']);
        
        $response = $this->post('/api/register/email',[
                'account' => 'harry@'
            ]
        );
        
        $this->assertFalse($response['status']);
        
        $response = $this->post('/api/register/email',[
                'account' => 'harry@gmail.8899.com.tw,harry+2@gmail.8899.com.tw'
            ]
        );
    
        $this->assertFalse($response['status']);
        
        $response = $this->post('/api/register/email',[
                'account' => 'harry@gmail.8899.com.tw;r'
            ]
        );
    
        $this->assertFalse($response['status']);
        
        $response = $this->post('/api/register/email',[
                'account' => 'harry @mail.8899.com.tw'
            ]
        );
    
        $this->assertFalse($response['status']);
        
        $response = $this->post('/api/register/email',[
                'account' => 'harry@ mail.8899.com.tw'
            ]
        );
    
        $this->assertFalse($response['status']);
        
        $response = $this->post('/api/register/email',[
                'account' => '信箱帳號!!@ mail.8899.com.tw'
            ]
        );
    
        $this->assertFalse($response['status']);
        
        $response = $this->post('/api/register/email',[
                'account' => 'harry@gmail.8899.com.tw'
            ]
        );
    
        $this->assertFalse($response['status']);
        
        $response = $this->post('/api/register/email',[
                'account' => 'harryharryharryharryharryharryharryharryharryharryharryharryharryharryharryharryharryharryharryharryharryharryharryharry@mail.8899.abc.123.tw'
            ]
        );
    
        $this->assertFalse($response['status']);
        //信箱格式錯誤 END
    }
    
    //帳號已被註冊
    public function test_send_mail_has_register(): void
    {
        $response = $this->post('/api/register/email',[
                'account' => 'harry+1@mail.8899.com.tw'
            ]
        );
    
        $this->assertFalse($response['status']);
    }
    
    //等待信件寄送冷卻時間
    public function test_send_mail_has_cooldown(): void
    {
        
        $this->post('/api/register/email',[
                'account' => 'harry@mail.8899.com.tw'
            ]
        );
        
        $response = $this->post('/api/register/email',[
                'account' => 'harry@mail.8899.com.tw'
            ]
        );
    
        $this->assertFalse($response['status']);
    }
    
    //冷卻時間結束，再次寄送信件
    public function test_send_mail_cooldown_end(): void
    {
        
        $this->post('/api/register/email',[
                'account' => 'harry@mail.8899.com.tw'
            ]
        );
        
        sleep(4);
        
        $response = $this->post('/api/register/email',[
                'account' => 'harry@mail.8899.com.tw'
            ]
        );
    
        $this->assertTrue($response['status']);
    }
    
    
    //短時間請求過多，封鎖IP十分鐘
    public function test_send_mail_has_block_ip(): void
    {
        config([
            'bstyle.ip.limit_count' => 5
        ]);
        
        $this->post('/api/register/email',[
                'account' => 'harry@mail.8899.com.tw'
            ]
        );
        
        $this->post('/api/register/email',[
                'account' => 'harry@mail.8899.com.tw'
            ]
        );
        
        $this->post('/api/register/email',[
                'account' => 'harry@mail.8899.com.tw'
            ]
        );
        
        $this->post('/api/register/email',[
                'account' => 'harry@mail.8899.com.tw'
            ]
        );
        
        $this->post('/api/register/email',[
                'account' => 'harry@mail.8899.com.tw'
            ]
        );
        
        $response = $this->post('/api/register/email',[
                'account' => 'harry@mail.8899.com.tw'
            ]
        );
    
        $this->assertFalse($response['status']);
    }
    
    //驗證碼檢查成功
    public function test_send_mail_check_mail_code_successfully(): void
    {
        $CacheRepository = new CacheRepository;
        
        $this->post('/api/register/email',[
                'account' => 'harry@mail.8899.com.tw'
            ]
        );
        
        $email_token = $CacheRepository->getEmailToken('register', 'harry@mail.8899.com.tw');
        
        $response = $this->patch('/api/register/email',[
                'account' => 'harry@mail.8899.com.tw',
                'email_token' => $email_token
            ]
        );
    
        $this->assertTrue($response['status']);
    }
    
    //驗證碼檢查錯誤
    public function test_send_mail_check_mail_code_failed(): void
    {
        $CacheRepository = new CacheRepository;
        
        $this->post('/api/register/email',[
                'account' => 'harry@mail.8899.com.tw'
            ]
        );
        
        $email_token = $CacheRepository->getEmailToken('register', 'harry@mail.8899.com.tw');
        
        $response = $this->patch('/api/register/email',[
                'account' => 'harry@mail.8899.com.tw',
                'email_token' => 'JJJJJJJJJJJJJJJJJ'
            ]
        );
    
        $this->assertFalse($response['status']);
    }
    
    //驗證碼檢查-參數格式錯誤
    public function test_send_mail_check_mail_code_invalid(): void
    {
        
        $CacheRepository = new CacheRepository;
        
        $this->post('/api/register/email',[
                'account' => 'harry@mail.8899.com.tw'
            ]
        );
        
        $email_token = $CacheRepository->getEmailToken('register', 'harry@mail.8899.com.tw');
        
        $_email_token = $email_token.'sssss';
        
        //驗證碼錯誤 START
        $response = $this->patch('/api/register/email',[
                'account' => 'harry@mail.8899.com.tw',
                'email_token' => $_email_token
            ]
        );
    
        $this->assertFalse($response['status']);
        //驗證碼錯誤 END
        
        
        //參數空值 START
        $response = $this->patch('/api/register/email',[
                'account' => '',
                'email_token' => ''
            ]
        );
    
        $this->assertFalse($response['status']);
        
        $response = $this->patch('/api/register/email',[
                'account' => 'harry@mail.8899.com.tw',
                'email_token' => ''
            ]
        );
    
        $this->assertFalse($response['status']);
        
        $response = $this->patch('/api/register/email',[
                'account' => '',
                'email_token' => $email_token
            ]
        );
    
        $this->assertFalse($response['status']);
        //參數空值 END
        
        //信箱格式錯誤 START
        $response = $this->patch('/api/register/email',[
                'account' => 'harry',
                'email_token' => $email_token
            ]
        );
    
        $this->assertFalse($response['status']);
        
        $response = $this->patch('/api/register/email',[
                'account' => 'harry@',
                'email_token' => $email_token
            ]
        );
    
        $this->assertFalse($response['status']);
        
        $response = $this->patch('/api/register/email',[
                'account' => 'harry @mail.8899.com.tw',
                'email_token' => $email_token
            ]
        );
    
        $this->assertFalse($response['status']);
        
        $response = $this->patch('/api/register/email',[
                'account' => 'harry@ mail.8899.com.tw',
                'email_token' => $email_token
            ]
        );
    
        $this->assertFalse($response['status']);
        
        $response = $this->patch('/api/register/email',[
                'account' => 'harry@gmail.8899.com.tw',
                'email_token' => $email_token
            ]
        );
    
        $this->assertFalse($response['status']);
        //信箱格式錯誤 END
        
        //非法字元 START
        $response = $this->patch('/api/register/email',[
                'account' => '我是中文加符號!^(&^%@gmail.com',
                'email_token' => $email_token
            ]
        );
    
        $this->assertFalse($response['status']);
        
        $response = $this->patch('/api/register/email',[
                'account' => 'harry@mail.8899.com.tw',
                'email_token' => '我是中文加符號!##$%%'
            ]
        );
    
        $this->assertFalse($response['status']);
        
        $response = $this->patch('/api/register/email',[
                'account' => '我是中文加符號!^(&^%@gmail.com',
                'email_token' => '我是中文加符號!##$%%'
            ]
        );
    
        $this->assertFalse($response['status']);
        //非法字元 END
        
        //參數長度超出範圍 START
        $response = $this->patch('/api/register/email',[
                'account' => 'harryharryharryharryharryharryharryharryharryharryharryharryharryharryharryharryharryharryharryharryharryharryharryharryharryharryharryharryharryharryharryharryharry@mail.8899.com.tw',
                'email_token' => $email_token
            ]
        );
    
        $this->assertFalse($response['status']);
        
        $response = $this->patch('/api/register/email',[
                'account' => 'harry@mail.8899.com.tw',
                'email_token' => '1231231231231231231231231231231231231231231231231231231231231231231231231231231231232131231231231231231231231231231231231'
            ]
        );
    
        $this->assertFalse($response['status']);
        
        $response = $this->patch('/api/register/email',[
                'account' => 'harryharryharryharryharryharryharryharryharryharryharryharryharryharryharryharryharryharryharryharryharryharryharryharryharryharryharryharryharryharryharryharryharry@mail.8899.com.tw',
                'email_token' => '1231231231231231231231231231231231231231231231231231231231231231231231231231231231232131231231231231231231231231231231231'
            ]
        );
    
        $this->assertFalse($response['status']);
        //參數長度超出範圍 END
    }
    
    //驗證碼已過期
    public function test_send_mail_check_mail_code_expired(): void
    {
        config([
            'bstyle.email.expires_seconds' => 3,
            'bstyle.final.expires_seconds' => 3,
        ]);
        
        $CacheRepository = new CacheRepository;
        
        $this->post('/api/register/email',[
                'account' => 'harry@mail.8899.com.tw'
            ]
        );
        
        $email_token = $CacheRepository->getEmailToken('register', 'harry@mail.8899.com.tw');
        
        sleep(4);
        
        $response = $this->patch('/api/register/email',[
                'account' => 'harry@mail.8899.com.tw',
                'email_token' => $email_token
            ]
        );
    
        $this->assertFalse($response['status']);
    }
    
    //短時間請求過多，封鎖IP十分鐘
    public function test_send_mail_check_mail_code_has_block_ip(): void
    {
        config([
            'bstyle.ip.limit_count' => 5
        ]);
        
        $CacheRepository = new CacheRepository;
        
        $this->post('/api/register/email',[
                'account' => 'harry@mail.8899.com.tw'
            ]
        );
        
        $email_token = $CacheRepository->getEmailToken('register', 'harry@mail.8899.com.tw');
        
        $this->patch('/api/register/email',[
                'account' => 'harry@mail.8899.com.tw',
                'email_token' => $email_token
            ]
        );
        
        $this->patch('/api/register/email',[
                'account' => 'harry@mail.8899.com.tw',
                'email_token' => $email_token
            ]
        );
        
        $this->patch('/api/register/email',[
                'account' => 'harry@mail.8899.com.tw',
                'email_token' => $email_token
            ]
        );
        
        $this->patch('/api/register/email',[
                'account' => 'harry@mail.8899.com.tw',
                'email_token' => $email_token
            ]
        );
        
        $this->patch('/api/register/email',[
                'account' => 'harry@mail.8899.com.tw',
                'email_token' => $email_token
            ]
        );
        
        $response = $this->patch('/api/register/email',[
                'account' => 'harry@mail.8899.com.tw',
                'email_token' => $email_token
            ]
        );
    
        $this->assertFalse($response['status']);
    }
    
    //註冊成功(驗證碼正確)
    
}