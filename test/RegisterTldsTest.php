<?php 
declare(strict_types=1);
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

require_once(realpath(dirname(__FILE__))."/../../../../init.php");
require_once(realpath(dirname(__FILE__))."/../vendor/autoload.php");
require_once(realpath(dirname(__FILE__))."/../lib/TestLib.php");

final class RegisterTldsTest extends TestCase
{
    protected function setUp(): void
    {
        // Create a new client
    }
    public static function additionProvider(): array
    {
        return [
            ['register', "com", [], []],
            ['register',"asia", [], ["countrycode" => "CN"]],
            ['register',"asia", [], ["countrycode" => "DE"]],
            ['register',"ca", ["Legal Type" => "Corporation"], ["admincompanyname" => "Same INC", "companyname" => "Same INC", "country" => "CA", "state" => "ON"]],
            ['register',"ca", ["Legal Type" => "Canadian Citizen","Canadian Citizen" => true], ["admincompanyname" => "Same INC", "companyname" => "Same INC2"]],
            ['register',"ca", ["Legal Type" => "Trade-mark registered in Canada", "Trademark Number" => "2342342", "Trademark Name" =>"Same INC", "Trademark Country" => "CA"], ["admincompanyname" => "Same INC", "companyname" => "Same INC"]],
            ['register',"it", ["Legal Type" => "Italian and foreign natural persons", "Tax ID" => "1234567"], []],
            ['register',"it", ["Legal Type" => "Italian and foreign natural persons", "Tax ID" => "1234567"], ["companyname" => null]],
            ['register',"it", ["Legal Type" => "Companies/one man companies", "Tax ID" => "1234567"], ["companyname" => "test inc"]],
            ['register',"it", ["Legal Type" => "Companies/one man companies", "Tax ID" => "1234567"], ["companyname" => "test inc"]],
            ['register',"at", [], []],
            ['transfer',"at", [], []]
        ];
    }

    #[DataProvider('additionProvider')]
    public function testOrder($command, $tld, $additionalFields, $override): void
    {
        $result = (array) TestLib::$command($tld, $additionalFields, $override);
        $this->assertArrayHasKey('status', $result, "Error: " . $result["error"]);        
    }

}   