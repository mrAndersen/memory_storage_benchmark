<?php


use Blablacar\Memcached\Client;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;

include __DIR__ . '/vendor/autoload.php';


$dataset = [
    'small' => str_repeat("abc", 10),
    'medium' => str_repeat("abc", 1024),
    'large' => str_repeat("abc", 1024 * 1024),
    'extra_large' => str_repeat("abc", 1024 * 1024 * 20)
];

function test_client($title, $client, &$dataset)
{
    $testKey = "test_key";
    $results[] = $title;

    foreach ($dataset as $title => $data) {
        $start = microtime(true);
        $client->set($testKey, $data);
        $results[] = end_timer($start);
    }


    $start = microtime(true);
    $client->get($testKey);
    $results[] = end_timer($start);

    return $results;
}

function test_redis(array &$dataset)
{
    $client = new \Predis\Client([
        'scheme' => 'tcp',
        'host' => 'localhost',
        'port' => 6379,
    ]);

    return test_client("Redis (PHP)", $client, $dataset);
}

function test_redis_c_client(array &$dataset)
{
    $client = new Redis();
    $client->connect("localhost");

    return test_client("Redis (C)", $client, $dataset);
}

function test_memcache(array &$dataset)
{
    $client = new Memcache();
    $client->connect("localhost", 11211);

    return test_client("Memcache (С)", $client, $dataset);
}

function test_memcached(array &$dataset)
{
    $client = new Memcached(null);
    $client->addServer('localhost', 11211);

    return test_client("Memcached (С)", $client, $dataset);
}

function end_timer($start)
{
    return format_time((microtime(true) - $start) * 1000);
}

function format_time($time)
{
    return sprintf("%.2f ms", $time);
}


$output = new ConsoleOutput();
$input = new ArgvInput();

$result = [];
$result = array_merge($result, [test_memcached($dataset)]);
$result = array_merge($result, [test_memcache($dataset)]);
$result = array_merge($result, [test_redis($dataset)]);
$result = array_merge($result, [test_redis_c_client($dataset)]);

$table = new Table($output);
$table->setHeaders(['#', '10 byte', '1 Kb', '1 Mb', "20 Mb", "Get 20 Mb"]);
$table->setRows($result);
$table->render();