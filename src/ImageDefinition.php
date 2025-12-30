<?php

namespace o6web\FaviconCollection;

class ImageDefinition
{
	private int $size;
	private bool $transparentBackground;
	private bool $roundEdges;
	private string $outputFilenamePrefix;

	public function __construct(int $size, bool $transparentBackground, bool $roundEdges, string $outputFilenamePrefix)
	{
		$this->size = $size;
		$this->transparentBackground = $transparentBackground;
		$this->roundEdges = $roundEdges;
		$this->outputFilenamePrefix = $outputFilenamePrefix;
	}

	public function getSize(): int
	{
		return $this->size;
	}

	public function hasTransparentBackground(): bool
	{
		return $this->transparentBackground;
	}

	public function hasRoundEdges(): bool
	{
		return $this->roundEdges;
	}

	public function getOutputFilenamePrefix(): string
	{
		return $this->outputFilenamePrefix;
	}
}