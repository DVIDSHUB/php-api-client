<?php

declare(strict_types=1);

namespace DvidsApi\Model;

/**
 * Represents job grade or rank information for an author
 */
readonly class JobGrade
{
    public function __construct(
        public string $name,
        public string $associatedPressName,
        public string $abbreviation,
        public ?Branch $branch = null,
        public ?string $jobGrade = null,
        public ?string $natoCode = null,
        public ?string $countryCode = null
    ) {
    }

    /**
     * Create JobGrade from API response data
     */
    public static function fromArray(array $data): self
    {
        $branch = null;
        if (isset($data['branch'])) {
            $branch = Branch::from($data['branch']);
        }

        return new self(
            name: $data['name'],
            associatedPressName: $data['associated_press_name'],
            abbreviation: $data['abbreviation'],
            branch: $branch,
            jobGrade: $data['job_grade'] ?? null,
            natoCode: $data['nato_code'] ?? null,
            countryCode: $data['country_code'] ?? null
        );
    }

    /**
     * Convert to array format
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'associated_press_name' => $this->associatedPressName,
            'abbreviation' => $this->abbreviation,
            'branch' => $this->branch?->value,
            'job_grade' => $this->jobGrade,
            'nato_code' => $this->natoCode,
            'country_code' => $this->countryCode
        ];
    }
}