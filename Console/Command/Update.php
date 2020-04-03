<?php
namespace Sga\IpRedirect\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Framework\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\Io\File as IoFile;
use Sga\IpRedirect\Model\ResourceModel\Location as ResourceModel;

class Update extends Command
{
    protected $_dir;
    protected $_path;
    protected $_resource;
    protected $_ioFile;

    protected $_fileLocation = 'GeoLite2-Country-Locations-en.csv';
    protected $_fileBlock = 'GeoLite2-Country-Blocks-IPv4.csv';
    protected $_columns = array(
        'start_ip_num', 'end_ip_num', 'country'
    );
    protected $_locations = array();

    public function __construct(
        ResourceModel $resource,
        DirectoryList $dir,
        IoFile $ioFile
    ){
        $this->_resource = $resource;
        $this->_dir = $dir;
        $this->_ioFile = $ioFile;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('ipredirect:update')->setDescription('Update IP/Country list');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->_path = $this->_dir->getPath('var').DIRECTORY_SEPARATOR.'ipredirect';
        $this->_ioFile->checkAndCreateFolder($this->_path);

        try {
            $output->writeln("Download files ...");
            $this->_downloadFiles();
            $output->writeln("- success");

            $output->writeln('Import lines ...');
            $this->_loadLocations();
            $this->_importLocations();
            $output->writeln("- success");

        } catch (\Exception $e) {
            $output->writeln($e->getMessage());
        }
    }

    protected function _downloadFiles()
    {
        $this->_ioFile->open(['path' => $this->_path]);

        // remove all files
        $files = $this->_ioFile->ls(IoFile::GREP_FILES);
        foreach ($files as $file) {
            $this->_ioFile->rm($file['text']);
        }

        // remove all dirs
        $dirs = $this->_ioFile->ls(IoFile::GREP_DIRS);
        foreach ($dirs as $dir) {
            $this->_ioFile->rmdir($dir['text'], true);
        }

        // download
        $zipFile = $this->_path.DIRECTORY_SEPARATOR.'GeoLite2-Country-CSV.zip';
        $content = file_get_contents('https://geolite.maxmind.com/download/geoip/database/GeoLite2-Country-CSV.zip');
        file_put_contents($zipFile, $content);

        // unzip
        $zip = new \ZipArchive();
        $status = $zip->open($zipFile);
        if ($status !== true) {
            throw new \Exception('Could not open zip archive '.$zipFile);
        }
        if (!$zip->extractTo($this->_path)) {
            throw new \Exception($this, 'Could not extract zip archive '.$this->_path);
        }
        $zip->close();

        // remove zip
        $this->_ioFile->rm('GeoLite2-Country-CSV.zip');

        // move right files
        $dirs = $this->_ioFile->ls(IoFile::GREP_DIRS);
        foreach ($dirs as $dir) {
            if (is_file($this->_path.DIRECTORY_SEPARATOR.$dir['text'].DIRECTORY_SEPARATOR.$this->_fileLocation)) {
                $this->_ioFile->mv($this->_path.DIRECTORY_SEPARATOR.$dir['text'].DIRECTORY_SEPARATOR.$this->_fileLocation, $this->_path.DIRECTORY_SEPARATOR.$this->_fileLocation);
            }
            if (is_file($this->_path.DIRECTORY_SEPARATOR.$dir['text'].DIRECTORY_SEPARATOR.$this->_fileBlock)) {
                $this->_ioFile->mv($this->_path.DIRECTORY_SEPARATOR.$dir['text'].DIRECTORY_SEPARATOR.$this->_fileBlock, $this->_path.DIRECTORY_SEPARATOR.$this->_fileBlock);
            }

            $this->_ioFile->rmdir($dir['text'], true);
        }
    }

    protected function _loadLocations()
    {
        $path = $this->_path.DIRECTORY_SEPARATOR.$this->_fileLocation;
        if (($handle = fopen($path, "r")) !== FALSE) {
            $i = 0;
            while ( ($data = fgetcsv($handle, 1024, ',')) !== FALSE) {
                if ($i > 0) {
                    $this->_locations[$data[0]] = $data[4];
                }
                $i++;
            }
        }
    }

    protected function _importLocations()
    {
        $path = $this->_path.DIRECTORY_SEPARATOR.$this->_fileBlock;
        if (($handle = fopen($path, "r")) !== FALSE) {
            $this->_cleanLocations();

            $i = 0;
            while (($data = fgetcsv($handle, 1024, ',')) !== FALSE) {
                if ($i > 0) {
                    if (isset($this->_locations[$data[1]])) {
                        $this->_importLocation($data, $this->_locations[$data[1]]);
                    }
                }
                $i++;
            }
        }
    }

    protected function _cleanLocations()
    {
        $this->_resource->getConnection()->truncateTable($this->_resource->getMainTable());
    }

    protected function _importLocation($data, $country)
    {
        list($ip, $mask) = explode('/', $data[0]);
        $ip2long = ip2long($ip);
        $min = ($ip2long >> (32 - $mask)) << (32 - $mask);
        $max = $ip2long | ~(-1 << (32 - $mask));
        $data = array(
            'start_ip_num' => $min,
            'end_ip_num' => $max,
            'country' => $country
        );

        $this->_resource->getConnection()->insert(
            $this->_resource->getMainTable(),
            $data
        );
    }
}