<?php


namespace ScandiPWA\LoggerInstaller;

use Composer\Composer;
use Composer\Installer\BinaryInstaller;
use Composer\Installer\LibraryInstaller;
use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;
use Composer\Repository\InstalledRepositoryInterface;
use Composer\Util\Filesystem;

class Installer extends LibraryInstaller
{
    /**
     * @var string
     */
    private $destinationDir;
    
    /**
     * @var string
     */
    private $defaultDiXml;
    
    /**
     * @var string
     */
    private $defaultDiXmlBackup;
    
    /**
     * Installer constructor.
     * @param IOInterface          $io
     * @param Composer             $composer
     * @param string               $type
     * @param Filesystem|null      $filesystem
     * @param BinaryInstaller|null $binaryInstaller
     */
    public function __construct(IOInterface $io, Composer $composer, $type = 'library', Filesystem $filesystem = null, BinaryInstaller $binaryInstaller = null)
    {
        $this->destinationDir = "." . DIRECTORY_SEPARATOR . "app" . DIRECTORY_SEPARATOR . "etc";
        $this->defaultDiXml = $this->destinationDir . DIRECTORY_SEPARATOR . "di.xml";
        $this->defaultDiXmlBackup = $this->destinationDir . DIRECTORY_SEPARATOR . "di.backup";
        parent::__construct($io, $composer, $type, $filesystem, $binaryInstaller);
    }
    
    /**
     * @inheritDoc
     */
    public function supports($packageType)
    {
        return "scandipwa-logger" === $packageType;
    }
    
    /**
     * @inheritDoc
     * @throws \Exception
     */
    public function uninstall(InstalledRepositoryInterface $repo, PackageInterface $package)
    {
        \file_put_contents("test", "uninstalled");
        $this->modifyDiXml('ScandiPWA\Logger\Cloud', 'Magento\Framework\Logger\Monolog');
    }
    
    /**
     * @inheritDoc
     * @throws \Exception
     */
    public function install(InstalledRepositoryInterface $repo, PackageInterface $package)
    {
        \file_put_contents("test", "installed");
        parent::install($repo, $package);
        $this->modifyDiXml('Magento\Framework\Logger\Monolog', 'ScandiPWA\Logger\Cloud');
    }
    
    /**
     * @param \DOMDocument $dom
     * @return \DOMNode|null
     * @throws \Exception
     */
    private function getNode(\DOMDocument $dom)
    {
        $xpath = new \DOMXPath($dom);
        $ns = $dom->documentElement->namespaceURI;
        if ($ns) {
            $xpath->registerNamespace("ns", $ns);
            $nodes = $xpath->query("//ns:preference[@for='Psr\Log\LoggerInterface']");
        } else {
            $nodes = $xpath->query("//preference[@for='Psr\Log\LoggerInterface']");
        }
        
        if (!$nodes->length) {
            throw new \Exception('No nodes matches the XPath query, unable to proceed');
        }
        
        return $nodes->item(0);
    }
    
    
    /**
     * @param PackageInterface $package
     * @throws \Exception
     */
    private function modifyDiXml(string $expectedBinding, string $newBinding)
    {
        $dom = new \DOMDocument();
        $dom->load($this->defaultDiXml);
        $preferenceNode = $this->getNode($dom);
        if (!$preferenceNode) {
            throw new \Exception('Node is empty');
        }
        $nodeType = $preferenceNode->getAttribute('type');
        if ($nodeType === $expectedBinding) {
            $preferenceNode->setAttribute('type', $newBinding);
            $preferenceNode->parentNode->replaceChild($preferenceNode, $preferenceNode);
            $dom->saveXML();
            return $dom->save($this->defaultDiXml);
        }
        throw new \Exception('Can not install custom logger');
    }
}