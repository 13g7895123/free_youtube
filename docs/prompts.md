WARNING - 2025-11-02 15:33:36 --> AuthFilter: No access_token cookie found
WARNING - 2025-11-02 15:33:36 --> AuthFilter: No access_token cookie found
WARNING - 2025-11-02 15:33:36 --> AuthFilter: No access_token cookie found
DEBUG - 2025-11-02 15:33:41 --> JWT initialized with secret key successfully
DEBUG - 2025-11-02 15:33:41 --> Generating access token for user 1 with expire seconds: 900
CRITICAL - 2025-11-02 15:33:41 --> Error: Class "Firebase\JWT\JWT" not found
[Method: GET, Route: api/auth/line/callback]
in APPPATH/Helpers/JwtHelper.php on line 85.
 1 APPPATH/Controllers/Auth.php(1195): App\Helpers\JwtHelper::generateAccessToken(1)
 2 APPPATH/Controllers/Auth.php(376): App\Controllers\Auth->generateUserToken(1)
 3 SYSTEMPATH/CodeIgniter.php(933): App\Controllers\Auth->lineCallback()
 4 SYSTEMPATH/CodeIgniter.php(507): CodeIgniter\CodeIgniter->runController(Object(App\Controllers\Auth))
 5 SYSTEMPATH/CodeIgniter.php(354): CodeIgniter\CodeIgniter->handleRequest(null, Object(Config\Cache), false)
 6 SYSTEMPATH/Boot.php(363): CodeIgniter\CodeIgniter->run()
 7 SYSTEMPATH/Boot.php(68): CodeIgniter\Boot::runCodeIgniter(Object(CodeIgniter\CodeIgniter))
 8 FCPATH/index.php(68): CodeIgniter\Boot::bootWeb(Object(Config\Paths))