<?php

declare (strict_types=1);
namespace PHPStan\Analyser\ResultCache;

use _HumbugBox221ad6f1b81f\Nette\Neon\Neon;
use PHPStan\Analyser\AnalyserResult;
use PHPStan\Analyser\Error;
use PHPStan\Command\Output;
use PHPStan\Dependency\ExportedNode;
use PHPStan\Dependency\ExportedNodeFetcher;
use PHPStan\File\FileReader;
use PHPStan\File\FileWriter;
use function array_fill_keys;
use function array_key_exists;
class ResultCacheManager
{
    private const CACHE_VERSION = 'v5-exportedNodes';
    /** @var ExportedNodeFetcher */
    private $exportedNodeFetcher;
    /** @var string */
    private $cacheFilePath;
    /** @var string */
    private $tempResultCachePath;
    /** @var string[] */
    private $analysedPaths;
    /** @var string[] */
    private $composerAutoloaderProjectPaths;
    /** @var string[] */
    private $stubFiles;
    /** @var string */
    private $usedLevel;
    /** @var string|null */
    private $cliAutoloadFile;
    /** @var array<string, string> */
    private $fileHashes = [];
    /** @var array<string, string> */
    private $fileReplacements = [];
    /**
     * @param ExportedNodeFetcher $exportedNodeFetcher
     * @param string $cacheFilePath
     * @param string $tempResultCachePath
     * @param string[] $analysedPaths
     * @param string[] $composerAutoloaderProjectPaths
     * @param string[] $stubFiles
     * @param string $usedLevel
     * @param string|null $cliAutoloadFile
     * @param array<string, string> $fileReplacements
     */
    public function __construct(\PHPStan\Dependency\ExportedNodeFetcher $exportedNodeFetcher, string $cacheFilePath, string $tempResultCachePath, array $analysedPaths, array $composerAutoloaderProjectPaths, array $stubFiles, string $usedLevel, ?string $cliAutoloadFile, array $fileReplacements)
    {
        $this->exportedNodeFetcher = $exportedNodeFetcher;
        $this->cacheFilePath = $cacheFilePath;
        $this->tempResultCachePath = $tempResultCachePath;
        $this->analysedPaths = $analysedPaths;
        $this->composerAutoloaderProjectPaths = $composerAutoloaderProjectPaths;
        $this->stubFiles = $stubFiles;
        $this->usedLevel = $usedLevel;
        $this->cliAutoloadFile = $cliAutoloadFile;
        $this->fileReplacements = $fileReplacements;
    }
    /**
     * @param string[] $allAnalysedFiles
     * @param mixed[]|null $projectConfigArray
     * @param bool $debug
     * @return ResultCache
     */
    public function restore(array $allAnalysedFiles, bool $debug, bool $onlyFiles, ?array $projectConfigArray, \PHPStan\Command\Output $output, ?string $resultCacheName = null) : \PHPStan\Analyser\ResultCache\ResultCache
    {
        if ($debug) {
            if ($output->isDebug()) {
                $output->writeLineFormatted('Result cache not used because of debug mode.');
            }
            return new \PHPStan\Analyser\ResultCache\ResultCache($allAnalysedFiles, \true, \time(), [], [], []);
        }
        if ($onlyFiles) {
            if ($output->isDebug()) {
                $output->writeLineFormatted('Result cache not used because only files were passed as analysed paths.');
            }
            return new \PHPStan\Analyser\ResultCache\ResultCache($allAnalysedFiles, \true, \time(), [], [], []);
        }
        $cacheFilePath = $this->cacheFilePath;
        if ($resultCacheName !== null) {
            $tmpCacheFile = $this->tempResultCachePath . '/' . $resultCacheName . '.php';
            if (\is_file($tmpCacheFile)) {
                $cacheFilePath = $tmpCacheFile;
            }
        }
        if (!\is_file($cacheFilePath)) {
            if ($output->isDebug()) {
                $output->writeLineFormatted('Result cache not used because the cache file does not exist.');
            }
            return new \PHPStan\Analyser\ResultCache\ResultCache($allAnalysedFiles, \true, \time(), [], [], []);
        }
        try {
            $data = (require $cacheFilePath);
        } catch (\Throwable $e) {
            if ($output->isDebug()) {
                $output->writeLineFormatted(\sprintf('Result cache not used because an error occurred while loading the cache file: %s', $e->getMessage()));
            }
            @\unlink($cacheFilePath);
            return new \PHPStan\Analyser\ResultCache\ResultCache($allAnalysedFiles, \true, \time(), [], [], []);
        }
        if (!\is_array($data)) {
            @\unlink($cacheFilePath);
            if ($output->isDebug()) {
                $output->writeLineFormatted('Result cache not used because the cache file is corrupted.');
            }
            return new \PHPStan\Analyser\ResultCache\ResultCache($allAnalysedFiles, \true, \time(), [], [], []);
        }
        if ($data['meta'] !== $this->getMeta($projectConfigArray)) {
            if ($output->isDebug()) {
                $output->writeLineFormatted('Result cache not used because the metadata do not match.');
            }
            return new \PHPStan\Analyser\ResultCache\ResultCache($allAnalysedFiles, \true, \time(), [], [], []);
        }
        if (\time() - $data['lastFullAnalysisTime'] >= 60 * 60 * 24 * 7) {
            if ($output->isDebug()) {
                $output->writeLineFormatted('Result cache not used because it\'s more than 7 days since last full analysis.');
            }
            // run full analysis if the result cache is older than 7 days
            return new \PHPStan\Analyser\ResultCache\ResultCache($allAnalysedFiles, \true, \time(), [], [], []);
        }
        $invertedDependencies = $data['dependencies'];
        $deletedFiles = \array_fill_keys(\array_keys($invertedDependencies), \true);
        $filesToAnalyse = [];
        $invertedDependenciesToReturn = [];
        $errors = $data['errorsCallback']();
        $exportedNodes = $data['exportedNodesCallback']();
        $filteredErrors = [];
        $filteredExportedNodes = [];
        $newFileAppeared = \false;
        foreach ($allAnalysedFiles as $analysedFile) {
            if (\array_key_exists($analysedFile, $errors)) {
                $filteredErrors[$analysedFile] = $errors[$analysedFile];
            }
            if (\array_key_exists($analysedFile, $exportedNodes)) {
                $filteredExportedNodes[$analysedFile] = $exportedNodes[$analysedFile];
            }
            if (!\array_key_exists($analysedFile, $invertedDependencies)) {
                // new file
                $filesToAnalyse[] = $analysedFile;
                $newFileAppeared = \true;
                continue;
            }
            unset($deletedFiles[$analysedFile]);
            $analysedFileData = $invertedDependencies[$analysedFile];
            $cachedFileHash = $analysedFileData['fileHash'];
            $dependentFiles = $analysedFileData['dependentFiles'];
            $invertedDependenciesToReturn[$analysedFile] = $dependentFiles;
            $currentFileHash = $this->getFileHash($analysedFile);
            if ($cachedFileHash === $currentFileHash) {
                continue;
            }
            $filesToAnalyse[] = $analysedFile;
            if (!\array_key_exists($analysedFile, $filteredExportedNodes)) {
                continue;
            }
            $cachedFileExportedNodes = $filteredExportedNodes[$analysedFile];
            if (\count($dependentFiles) === 0) {
                continue;
            }
            if (!$this->exportedNodesChanged($analysedFile, $cachedFileExportedNodes)) {
                continue;
            }
            foreach ($dependentFiles as $dependentFile) {
                if (!\is_file($dependentFile)) {
                    continue;
                }
                $filesToAnalyse[] = $dependentFile;
            }
        }
        foreach (\array_keys($deletedFiles) as $deletedFile) {
            if (!\array_key_exists($deletedFile, $invertedDependencies)) {
                continue;
            }
            $deletedFileData = $invertedDependencies[$deletedFile];
            $dependentFiles = $deletedFileData['dependentFiles'];
            foreach ($dependentFiles as $dependentFile) {
                if (!\is_file($dependentFile)) {
                    continue;
                }
                $filesToAnalyse[] = $dependentFile;
            }
        }
        if ($newFileAppeared) {
            foreach (\array_keys($filteredErrors) as $fileWithError) {
                $filesToAnalyse[] = $fileWithError;
            }
        }
        return new \PHPStan\Analyser\ResultCache\ResultCache(\array_unique($filesToAnalyse), \false, $data['lastFullAnalysisTime'], $filteredErrors, $invertedDependenciesToReturn, $filteredExportedNodes);
    }
    /**
     * @param string $analysedFile
     * @param array<int, ExportedNode> $cachedFileExportedNodes
     * @return bool
     */
    private function exportedNodesChanged(string $analysedFile, array $cachedFileExportedNodes) : bool
    {
        if (\array_key_exists($analysedFile, $this->fileReplacements)) {
            $analysedFile = $this->fileReplacements[$analysedFile];
        }
        $fileExportedNodes = $this->exportedNodeFetcher->fetchNodes($analysedFile);
        if (\count($fileExportedNodes) !== \count($cachedFileExportedNodes)) {
            return \true;
        }
        foreach ($fileExportedNodes as $i => $fileExportedNode) {
            $cachedExportedNode = $cachedFileExportedNodes[$i];
            if (!$cachedExportedNode->equals($fileExportedNode)) {
                return \true;
            }
        }
        return \false;
    }
    /**
     * @param AnalyserResult $analyserResult
     * @param ResultCache $resultCache
     * @param mixed[]|null $projectConfigArray
     * @param bool|string $save
     * @return ResultCacheProcessResult
     * @throws \PHPStan\ShouldNotHappenException
     */
    public function process(\PHPStan\Analyser\AnalyserResult $analyserResult, \PHPStan\Analyser\ResultCache\ResultCache $resultCache, \PHPStan\Command\Output $output, bool $onlyFiles, ?array $projectConfigArray, $save) : \PHPStan\Analyser\ResultCache\ResultCacheProcessResult
    {
        $internalErrors = $analyserResult->getInternalErrors();
        $freshErrorsByFile = [];
        foreach ($analyserResult->getErrors() as $error) {
            $freshErrorsByFile[$error->getFilePath()][] = $error;
        }
        $doSave = function (array $errorsByFile, ?array $dependencies, array $exportedNodes, ?string $resultCacheName) use($internalErrors, $resultCache, $output, $onlyFiles, $projectConfigArray) : bool {
            if ($onlyFiles) {
                if ($output->isDebug()) {
                    $output->writeLineFormatted('Result cache was not saved because only files were passed as analysed paths.');
                }
                return \false;
            }
            if ($dependencies === null) {
                if ($output->isDebug()) {
                    $output->writeLineFormatted('Result cache was not saved because of error in dependencies.');
                }
                return \false;
            }
            if (\count($internalErrors) > 0) {
                if ($output->isDebug()) {
                    $output->writeLineFormatted('Result cache was not saved because of internal errors.');
                }
                return \false;
            }
            foreach ($errorsByFile as $errors) {
                foreach ($errors as $error) {
                    if (!$error->hasNonIgnorableException()) {
                        continue;
                    }
                    if ($output->isDebug()) {
                        $output->writeLineFormatted(\sprintf('Result cache was not saved because of non-ignorable exception: %s', $error->getMessage()));
                    }
                    return \false;
                }
            }
            $this->save($resultCache->getLastFullAnalysisTime(), $resultCacheName, $errorsByFile, $dependencies, $exportedNodes, $projectConfigArray);
            if ($output->isDebug()) {
                $output->writeLineFormatted('Result cache is saved.');
            }
            return \true;
        };
        if ($resultCache->isFullAnalysis()) {
            $saved = \false;
            if ($save !== \false) {
                $saved = $doSave($freshErrorsByFile, $analyserResult->getDependencies(), $analyserResult->getExportedNodes(), \is_string($save) ? $save : null);
            } else {
                if ($output->isDebug()) {
                    $output->writeLineFormatted('Result cache was not saved because it was not requested.');
                }
            }
            return new \PHPStan\Analyser\ResultCache\ResultCacheProcessResult($analyserResult, $saved);
        }
        $errorsByFile = $this->mergeErrors($resultCache, $freshErrorsByFile);
        $dependencies = $this->mergeDependencies($resultCache, $analyserResult->getDependencies());
        $exportedNodes = $this->mergeExportedNodes($resultCache, $analyserResult->getExportedNodes());
        $saved = \false;
        if ($save !== \false) {
            $saved = $doSave($errorsByFile, $dependencies, $exportedNodes, \is_string($save) ? $save : null);
        }
        $flatErrors = [];
        foreach ($errorsByFile as $fileErrors) {
            foreach ($fileErrors as $fileError) {
                $flatErrors[] = $fileError;
            }
        }
        return new \PHPStan\Analyser\ResultCache\ResultCacheProcessResult(new \PHPStan\Analyser\AnalyserResult($flatErrors, $internalErrors, $dependencies, $exportedNodes, $analyserResult->hasReachedInternalErrorsCountLimit()), $saved);
    }
    /**
     * @param ResultCache $resultCache
     * @param array<string, array<Error>> $freshErrorsByFile
     * @return array<string, array<Error>>
     */
    private function mergeErrors(\PHPStan\Analyser\ResultCache\ResultCache $resultCache, array $freshErrorsByFile) : array
    {
        $errorsByFile = $resultCache->getErrors();
        foreach ($resultCache->getFilesToAnalyse() as $file) {
            if (!\array_key_exists($file, $freshErrorsByFile)) {
                unset($errorsByFile[$file]);
                continue;
            }
            $errorsByFile[$file] = $freshErrorsByFile[$file];
        }
        return $errorsByFile;
    }
    /**
     * @param ResultCache $resultCache
     * @param array<string, array<string>>|null $freshDependencies
     * @return array<string, array<string>>|null
     */
    private function mergeDependencies(\PHPStan\Analyser\ResultCache\ResultCache $resultCache, ?array $freshDependencies) : ?array
    {
        if ($freshDependencies === null) {
            return null;
        }
        $cachedDependencies = [];
        $resultCacheDependencies = $resultCache->getDependencies();
        $filesNoOneIsDependingOn = \array_fill_keys(\array_keys($resultCacheDependencies), \true);
        foreach ($resultCacheDependencies as $file => $filesDependingOnFile) {
            foreach ($filesDependingOnFile as $fileDependingOnFile) {
                $cachedDependencies[$fileDependingOnFile][] = $file;
                unset($filesNoOneIsDependingOn[$fileDependingOnFile]);
            }
        }
        foreach (\array_keys($filesNoOneIsDependingOn) as $file) {
            if (\array_key_exists($file, $cachedDependencies)) {
                throw new \PHPStan\ShouldNotHappenException();
            }
            $cachedDependencies[$file] = [];
        }
        $newDependencies = $cachedDependencies;
        foreach ($resultCache->getFilesToAnalyse() as $file) {
            if (!\array_key_exists($file, $freshDependencies)) {
                unset($newDependencies[$file]);
                continue;
            }
            $newDependencies[$file] = $freshDependencies[$file];
        }
        return $newDependencies;
    }
    /**
     * @param ResultCache $resultCache
     * @param array<string, array<ExportedNode>> $freshExportedNodes
     * @return array<string, array<ExportedNode>>
     */
    private function mergeExportedNodes(\PHPStan\Analyser\ResultCache\ResultCache $resultCache, array $freshExportedNodes) : array
    {
        $newExportedNodes = $resultCache->getExportedNodes();
        foreach ($resultCache->getFilesToAnalyse() as $file) {
            if (!\array_key_exists($file, $freshExportedNodes)) {
                unset($newExportedNodes[$file]);
                continue;
            }
            $newExportedNodes[$file] = $freshExportedNodes[$file];
        }
        return $newExportedNodes;
    }
    /**
     * @param int $lastFullAnalysisTime
     * @param string|null $resultCacheName
     * @param array<string, array<Error>> $errors
     * @param array<string, array<string>> $dependencies
     * @param array<string, array<ExportedNode>> $exportedNodes
     * @param mixed[]|null $projectConfigArray
     */
    private function save(int $lastFullAnalysisTime, ?string $resultCacheName, array $errors, array $dependencies, array $exportedNodes, ?array $projectConfigArray) : void
    {
        $invertedDependencies = [];
        $filesNoOneIsDependingOn = \array_fill_keys(\array_keys($dependencies), \true);
        foreach ($dependencies as $file => $fileDependencies) {
            foreach ($fileDependencies as $fileDep) {
                if (!\array_key_exists($fileDep, $invertedDependencies)) {
                    $invertedDependencies[$fileDep] = ['fileHash' => $this->getFileHash($fileDep), 'dependentFiles' => []];
                    unset($filesNoOneIsDependingOn[$fileDep]);
                }
                $invertedDependencies[$fileDep]['dependentFiles'][] = $file;
            }
        }
        foreach (\array_keys($filesNoOneIsDependingOn) as $file) {
            if (\array_key_exists($file, $invertedDependencies)) {
                throw new \PHPStan\ShouldNotHappenException();
            }
            if (!\is_file($file)) {
                continue;
            }
            $invertedDependencies[$file] = ['fileHash' => $this->getFileHash($file), 'dependentFiles' => []];
        }
        \ksort($errors);
        \ksort($invertedDependencies);
        foreach ($invertedDependencies as $file => $fileData) {
            $dependentFiles = $fileData['dependentFiles'];
            \sort($dependentFiles);
            $invertedDependencies[$file]['dependentFiles'] = $dependentFiles;
        }
        $template = <<<'php'
<?php declare(strict_types = 1);

return [
	'lastFullAnalysisTime' => %s,
	'meta' => %s,
	'errorsCallback' => static function (): array { return %s; },
	'dependencies' => %s,
	'exportedNodesCallback' => static function (): array { return %s; },
];
php;
        \ksort($exportedNodes);
        $file = $this->cacheFilePath;
        if ($resultCacheName !== null) {
            $file = $this->tempResultCachePath . '/' . $resultCacheName . '.php';
        }
        \PHPStan\File\FileWriter::write($file, \sprintf($template, \var_export($lastFullAnalysisTime, \true), \var_export($this->getMeta($projectConfigArray), \true), \var_export($errors, \true), \var_export($invertedDependencies, \true), \var_export($exportedNodes, \true)));
    }
    /**
     * @param mixed[]|null $projectConfigArray
     * @return mixed[]
     */
    private function getMeta(?array $projectConfigArray) : array
    {
        $extensions = \array_values(\array_filter(\get_loaded_extensions(), static function (string $extension) : bool {
            return $extension !== 'xdebug';
        }));
        \sort($extensions);
        if ($projectConfigArray !== null) {
            unset($projectConfigArray['parameters']['ignoreErrors']);
            unset($projectConfigArray['parameters']['tipsOfTheDay']);
            unset($projectConfigArray['parameters']['parallel']);
            unset($projectConfigArray['parameters']['internalErrorsCountLimit']);
            unset($projectConfigArray['parameters']['cache']);
            unset($projectConfigArray['parameters']['reportUnmatchedIgnoredErrors']);
            unset($projectConfigArray['parameters']['memoryLimitFile']);
            unset($projectConfigArray['parametersSchema']);
            $projectConfigArray = \_HumbugBox221ad6f1b81f\Nette\Neon\Neon::encode($projectConfigArray);
        }
        return ['cacheVersion' => self::CACHE_VERSION, 'phpstanVersion' => $this->getPhpStanVersion(), 'phpVersion' => \PHP_VERSION_ID, 'projectConfig' => $projectConfigArray, 'analysedPaths' => $this->analysedPaths, 'composerLocks' => $this->getComposerLocks(), 'cliAutoloadFile' => $this->cliAutoloadFile, 'phpExtensions' => $extensions, 'stubFiles' => $this->getStubFiles(), 'level' => $this->usedLevel];
    }
    private function getFileHash(string $path) : string
    {
        if (\array_key_exists($path, $this->fileReplacements)) {
            $path = $this->fileReplacements[$path];
        }
        if (\array_key_exists($path, $this->fileHashes)) {
            return $this->fileHashes[$path];
        }
        $contents = \PHPStan\File\FileReader::read($path);
        $contents = \str_replace("\r\n", "\n", $contents);
        $hash = \sha1($contents);
        $this->fileHashes[$path] = $hash;
        return $hash;
    }
    private function getPhpStanVersion() : string
    {
        try {
            return \_HumbugBox221ad6f1b81f\Jean85\PrettyVersions::getVersion('phpstan/phpstan')->getPrettyVersion();
        } catch (\OutOfBoundsException $e) {
            return 'Version unknown';
        }
    }
    /**
     * @return array<string, string>
     */
    private function getComposerLocks() : array
    {
        $locks = [];
        foreach ($this->composerAutoloaderProjectPaths as $autoloadPath) {
            $lockPath = $autoloadPath . '/composer.lock';
            if (!\is_file($lockPath)) {
                continue;
            }
            $locks[$lockPath] = $this->getFileHash($lockPath);
        }
        return $locks;
    }
    /**
     * @return array<string, string>
     */
    private function getStubFiles() : array
    {
        $stubFiles = [];
        foreach ($this->stubFiles as $stubFile) {
            $stubFiles[$stubFile] = $this->getFileHash($stubFile);
        }
        return $stubFiles;
    }
}
