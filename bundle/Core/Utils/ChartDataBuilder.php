<?php

declare(strict_types=1);

namespace CodeRhapsodie\IbexaMailingBundle\Core\Utils;

class ChartDataBuilder
{
    private string $title;

    /**
     * @var array<mixed>
     */
    private array $options;

    /**
     * @var array<mixed>
     */
    private array $dataSets;

    private string $type;

    /**
     * @var array<string>
     */
    private array $colorsSets;

    /**
     * @param array<mixed> $options
     */
    public function __construct(string $title, string $type, array $options = [])
    {
        $this->title = $title;
        $this->type = $type;
        $this->options = $options;
        $this->dataSets = [];
        $this->colorsSets = [
            '#ff6384', '#36a2eb', '#cc65fe', '#ffce56', '#EC644B', '#DB0A5B', '#E26A6A', '#DCC6E0', '#663399',
            '#913D88', '#BF55EC', '#9B59B6', '#446CB3', '#59ABE3', '#19B5FE', '#A2DED0', '#66CC99', '#00B16A',
            '#F4B350',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function __invoke(): array
    {
        $datasets = [];
        $labels = [];
        foreach ($this->dataSets as $dataSet) {
            $exportedDataset = [
                'data' => $dataSet['data'],
                'backgroundColor' => $dataSet['colors'] ?? $this->colorsSets,
            ];
            if (isset($dataSet['type'])) {
                $exportedDataset['type'] = $dataSet['type'];
                $exportedDataset['fill'] = false;
                $exportedDataset['borderColor'] = $dataSet['colors'] ?? $this->colorsSets;
            }

            $datasets[] = $exportedDataset;
            $labels = $dataSet['labels'];
        }

        $options = $this->options;
        $options['title']['display'] = true;
        $options['title']['text'] = $this->title;

        if ($this->type === 'bar') {
            $options['legend'] = false;
            $options['scales']['y']['ticks'] = [
                'stepSize' => 1,
                'beginAtZero' => true,
            ];
            $options['barThickness'] = 3;
        }

        return [
            'type' => $this->type,
            'options' => $options,
            'data' => [
                'datasets' => $datasets,
                'labels' => $labels,
            ],
        ];
    }

    /**
     * @param array<mixed>      $data
     * @param array<mixed>      $labels
     * @param array<mixed>|null $colors
     */
    public function addDataSet(array $data, array $labels, array $colors = null, string $type = null): self
    {
        if ($type === null) {
            $this->dataSets[] = [
                'data' => $data,
                'labels' => $labels,
                'colors' => $colors,
            ];

            return $this;
        }

        $this->dataSets[] = [
            'data' => $data,
            'labels' => $labels,
            'colors' => $colors,
            'type' => $type,
        ];

        return $this;
    }
}
