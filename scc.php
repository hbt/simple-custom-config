<?php

define('KEY_VALUE_SEPARATOR', '=');

function main($argv)
{
    $templateFilename = getTemplateFilename($argv);
    $data = extractDataAsArray($templateFilename);
    replaceFiles($data);
}

function getTemplateFilename($argv)
{
    // scc_config location
    $ret = 'scc_config.txt';
    if (isset($argv[1]))
    {
        $ret = $argv[1];
    }

    if (!file_exists($ret))
    {
        echo "File $ret was not found";
        exit(1);
    }

    return $ret;
}

function getDefaultKeys()
{
    return array(
        'postfix' => '%%',
        'prefix' => '%%',
        'tmpl' => '.template',
        'tmpl_regex' => '/.\.template\../'
    );
}

function extractDataAsArray($templateFilename)
{
    $ret = getDefaultKeys();

    // read file
    $content = file_get_contents($templateFilename);

    // replace defaults
    $lines = explode(PHP_EOL, $content);
    foreach ($lines as $line)
    {
        if (isLineValid($line))
        {
            $fields = explode(KEY_VALUE_SEPARATOR, $line);
            $key = substr($line, 0, stripos($line, KEY_VALUE_SEPARATOR));
            $value = substr($line, stripos($line, KEY_VALUE_SEPARATOR) + 1);

            $ret[$key] = $value;
        }
    }

    return $ret;
}

function isLineValid($str)
{
    return strlen(trim($str)) > 0 && stripos($str, KEY_VALUE_SEPARATOR) !== false;
}

function replaceFiles($data)
{
    // get matched files
    $directory = new RecursiveDirectoryIterator(getcwd());
    $flattened = new RecursiveIteratorIterator($directory);

    $files = new RegexIterator($flattened, $data['tmpl_regex']);

    foreach ($files as $file)
    {
        if ($file->isFile() && preg_match($data['tmpl_regex'], $file->getFilename()) && !preg_match('/\.svn/', $file->getFilename()))
        {
            $res = replaceFile($file, $data);
            if($res)
            {
                echo PHP_EOL . "Updated " . getCleanFilename($data['tmpl'], $file->getFileName()) . PHP_EOL;
            }
        }
    }
}

function getCleanFilename($pattern, $filename)
{
    $ret = str_replace($pattern, '', $filename);
    return $ret;
}

function replaceFile($file, $data)
{
    // validate
    $filepath = $file->getPathName();
    if (!file_exists($filepath))
        return;

    // read
    $content = file_get_contents($filepath);
    $ret = $content;

    // search & replace
    foreach ($data as $key => $value)
    {
        $fkey = $data['prefix'] . $key . $data['postfix'];
        $ret = str_replace($fkey, $value, $ret);
    }

    // write to file
    $nfilename = getCleanFilename($data['tmpl'], $file->getFileName());
    $nfilepath = str_replace($file->getFileName(), $nfilename, $filepath);

    $oldcontent = null;
    if(file_exists($nfilepath))
    {
        $oldcontent = file_get_contents($nfilepath);
    }

    if ($oldcontent !== $ret)
        file_put_contents($nfilepath, $ret);
    else
        $ret = null;

    return $ret;
}

main($argv);
?>
