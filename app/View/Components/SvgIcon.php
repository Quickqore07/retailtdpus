<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\Support\Facades\File;

class SvgIcon extends Component
{
    public string $name;
    public string $size;
    public ?string $width;
    public ?string $height;
    public string $color;
    public string $class;

    /**
     * Predefined sizes in pixels
     */
    protected array $sizes = [
        'xs' => 12,
        'sm' => 16,
        'md' => 20,
        'lg' => 24,
        'xl' => 32,
        '2xl' => 48,
    ];

    /**
     * Create a new component instance.
     */
    public function __construct(
        string $name,
        string $size = 'md',
        ?string $width = null,
        ?string $height = null,
        string $color = 'currentColor',
        string $class = ''
    ) {
        $this->name = $name;
        $this->size = $size;
        $this->width = $width;
        $this->height = $height;
        $this->color = $color;
        $this->class = $class;
    }

    /**
     * Get the SVG content
     */
    public function svgContent(): string
    {
        $path = resource_path("svg/{$this->name}.svg");

        if (!File::exists($path)) {
            return "<!-- SVG not found: {$this->name} -->";
        }

        $svg = File::get($path);

        // Get dimensions
        $width = $this->width ?? ($this->sizes[$this->size] ?? 20);
        $height = $this->height ?? ($this->sizes[$this->size] ?? 20);

        // Add px if numeric
        $widthPx = is_numeric($width) ? "{$width}px" : $width;
        $heightPx = is_numeric($height) ? "{$height}px" : $height;

        // Resolve color (handle CSS variables)
        $color = $this->color;
        if (str_starts_with($color, '--')) {
            $color = "var({$color})";
        }

        // Remove existing width/height attributes
        $svg = preg_replace('/\s*width="[^"]*"/', '', $svg);
        $svg = preg_replace('/\s*height="[^"]*"/', '', $svg);
        
        // Replace stroke="currentColor" with the actual color
        if ($color !== 'currentColor') {
            $svg = str_replace('stroke="currentColor"', "stroke=\"{$color}\"", $svg);
            $svg = str_replace('fill="currentColor"', "fill=\"{$color}\"", $svg);
        }

        // Add our attributes to the SVG tag
        $svg = preg_replace(
            '/<svg/',
            "<svg width=\"{$widthPx}\" height=\"{$heightPx}\" style=\"display: inline-block; vertical-align: middle;\"",
            $svg,
            1
        );

        return $svg;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render()
    {
        return view('components.svg-icon');
    }
}
