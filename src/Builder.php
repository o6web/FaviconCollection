<?php

namespace o6web\FaviconCollection;

use cjrasmussen\Color\General;
use cjrasmussen\Image\Resize;
use Ossobuffo\PhpIco\IcoConverter;
use ZipArchive;

class Builder
{
	private ?IcoConverter $icoConverter;
	private array $imageDefinitions;
	private string $tmpPath;
	private array $outputFiles = [];

	public function __construct(?IcoConverter $icoConverter = null)
	{
		$this->icoConverter = $icoConverter;

		$this->imageDefinitions = [
			// iPhone(first generation or 2G), iPhone 3G, iPhone 3GS
			new ImageDefinition(57, false, false, 'apple-touch-icon'),
			// windows phone small tile
			new ImageDefinition(70, false, false, 'windows'),
			// iPad and iPad mini @1x
			new ImageDefinition(76, false, false, 'apple-touch-icon'),
			// social media
			new ImageDefinition(100, true, false, 'favicon'),
			// iPhone something?
			new ImageDefinition(114, false, false, 'apple-touch-icon'),
			// iPhone 6/7, iPhone 6/7s, iPhone SE
			new ImageDefinition(120, false, false, 'apple-touch-icon'),
			// Android Regular
			new ImageDefinition(128, false, true, 'favicon'),
			// iPhone something?
			new ImageDefinition(144, false, false, 'apple-touch-icon'),
			// windows phone medium tile
			new ImageDefinition(150, false, false, 'windows'),
			// iPad / iPad Mini
			new ImageDefinition(152, false, false, 'apple-touch-icon'),
			// iPad Pro
			new ImageDefinition(167, false, false, 'apple-touch-icon'),
			// iPhone 6/7 Plus, iPhone 6/7s Plus
			new ImageDefinition(180, false, false, 'apple-touch-icon'),
			// Android Hi-Res
			new ImageDefinition(192, false, true, 'favicon'),
			// social media
			new ImageDefinition(200, true, false, 'favicon'),
			// windows phone large tile
			new ImageDefinition(310, false, false, 'favicon'),
		];

		$this->tmpPath = sys_get_temp_dir();
	}

	public function __destruct()
	{
		$this->removeOutputFiles();
	}

	/**
	 * Build a set of icons from the provided source file
	 *
	 * @param string $sourceFilePath
	 * @param string $backgroundColorHex
	 * @param int $gutter
	 * @param array $sizes
	 * @return $this
	 */
	public function build(string $sourceFilePath, string $backgroundColorHex = 'fff', int $gutter = 0, array $sizes = []): self
	{
		$sizes = $this->sanitizeSizes($sizes);
		$backgroundColorHex = $this->sanitizeBackgroundColor($backgroundColorHex);

		foreach ($this->imageDefinitions AS $imageDefinition) {
			/** @var ImageDefinition $imageDefinition */
			$size = $imageDefinition->getSize();
			if (in_array($size, $sizes, true)) {
				$outputPath = $this->tmpPath . $imageDefinition->getOutputFilenamePrefix() . '-' . str_pad($size, 3, '0', STR_PAD_LEFT) . '.png';

				$img = Resize::resize($sourceFilePath, $size, $size, 'png', (($imageDefinition->hasTransparentBackground()) ? null : $backgroundColorHex),
					$imageDefinition->hasRoundEdges(), $gutter);

				if (imagepng($img, $outputPath)) {
					$this->outputFiles[] = $outputPath;
				}

				imagedestroy($img);
			}
		}

		if ($this->icoConverter) {
			$icoOutput = $this->buildIcoFile($sourceFilePath);
			if ($icoOutput) {
				$this->outputFiles[] = $icoOutput;
			}
		}

		return $this;
	}

	/**
	 * Package the generated output files into a zip archive
	 *
	 * If an output path is supplied, the zip file will be copied to that location.
	 *
	 * @param string|null $outputPath
	 * @return $this
	 */
	public function zipOutputFiles(?string $outputPath = null): self
	{
		$zipFilePath = $this->tmpPath . 'faviconCollection.zip';

		$zip = new ZipArchive;
		$zip->open($zipFilePath, ZipArchive::CREATE);

		foreach ($this->outputFiles AS $file) {
			$zip->addFile($file, basename($file));
		}

		$zip->close();

		$this->removeOutputFiles();

		if ($outputPath) {
			copy($zipFilePath, $outputPath);
		} else {
			$this->outputFiles[] = $zipFilePath;
		}

		return $this;
	}

	/**
	 * Determine if any output files have been generated
	 *
	 * @return bool
	 */
	public function hasOutputFiles(): bool
	{
		return (count($this->outputFiles) > 0);
	}

	/**
	 * Return the list of generated output files
	 *
	 * @return array
	 */
	public function getOutputFiles(): array
	{
		return $this->outputFiles;
	}

	/**
	 * Sanitize and validate the array of provided output sizes
	 *
	 * @param array $sizes
	 * @return array
	 */
	private function sanitizeSizes(array $sizes): array
	{
		if (count($sizes) === 0) {
			// WE EXPLICITLY SPECIFIED NO OUTPUT SIZES
			return [];
		}

		$sanitizedSizes = array_map(static function ($size) {
			return (int)$size;
		}, $sizes);

		$defaultSizes = array_map(static function($imageDefinition) {
			/** @var ImageDefinition $imageDefinition */
			return $imageDefinition->getSize();
		}, $this->imageDefinitions);

		$outputSizes = array_intersect($sanitizedSizes, $defaultSizes);

		if (!count($outputSizes)) {
			return $defaultSizes;
		}

		return $outputSizes;
	}

	/**
	 * Sanitize the provided background color hex code, converting to white if it is invalid
	 *
	 * @param string $backgroundColorHex
	 * @return string
	 */
	private function sanitizeBackgroundColor(string $backgroundColorHex): string
	{
		$cleanedColor = General::cleanHexColor($backgroundColorHex);
		if ($cleanedColor === '') {
			$cleanedColor = 'fff';
		}

		return $cleanedColor;
	}

	/**
	 * Build a favicon.ico file from the specified source image
	 *
	 * @param string $sourceFilePath
	 * @return string|null
	 */
	private function buildIcoFile(string $sourceFilePath): ?string
	{
		$temporaryFilePath = $this->tmpPath . 'temp.png';

		[$width, $height] = getimagesize($sourceFilePath);
		$size = ($width > $height) ? $width : $height;

		$img = Resize::resize($sourceFilePath, $size, $size, 'png');
		imagepng($img, $temporaryFilePath);
		imagedestroy($img);

		$outputPath =  $this->tmpPath . 'favicon.ico';

		$this->icoConverter->addImage($temporaryFilePath, [[16, 16], [32, 32], [48, 48], [64, 64], [128, 128]]);
		$success = $this->icoConverter->saveIco($outputPath);

		unlink($temporaryFilePath);

		return ($success) ? $outputPath : null;
	}

	/**
	 * Remove the generated output files from the filesystem
	 *
	 * @return void
	 */
	private function removeOutputFiles(): void
	{
		foreach ($this->outputFiles AS $key => $filePath) {
			if ((file_exists($filePath)) && (unlink($filePath))) {
				unset($this->outputFiles[$key]);
			}
		}
	}
}