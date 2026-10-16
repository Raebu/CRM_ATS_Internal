<?php

namespace Tests\Unit\Talent;

use App\Services\Talent\CvProfileBuilder;
use Tests\TestCase;

final class CvProfileBuilderStructuredEntriesTest extends TestCase
{
    public function test_clearly_labelled_jobs_and_degrees_become_repeatable_entries(): void
    {
        $text = implode("\n", [
            'Alex Morgan',
            'WORK EXPERIENCE',
            'Engineering Manager - Example Technologies',
            'March 2022 - Present',
            'Led a team of 12 engineers.',
            'Software Engineer - Sample Systems',
            'January 2018 - February 2022',
            'Developed internal CRM systems.',
            'EDUCATION',
            'MSc Computer Science - Example University',
            '2017',
            'BSc Information Technology - Sample University',
            '2015',
            'SKILLS',
            'PHP, SQL, Laravel',
        ]);
        [$fields] = (new CvProfileBuilder)->extract($text, 'Alex Morgan', 'alex@example.invalid');
        $this->assertCount(2, $fields['employment_entries']);
        $this->assertSame('Engineering Manager', $fields['employment_entries'][0]['title']);
        $this->assertSame('Example Technologies', $fields['employment_entries'][0]['company']);
        $this->assertSame('2022-03', $fields['employment_entries'][0]['start']);
        $this->assertSame('yes', $fields['employment_entries'][0]['present']);
        $this->assertSame('2022-02', $fields['employment_entries'][1]['end']);
        $this->assertCount(2, $fields['education_entries']);
        $this->assertSame('Example University', $fields['education_entries'][0]['institution']);
        $this->assertArrayNotHasKey('graduation', $fields['education_entries'][0]);
        $this->assertStringContainsString('2017', $fields['education_entries'][0]['coursework']);
        $this->assertStringContainsString('Sample Systems', $fields['additional_information']);
        $this->assertSame('PHP, SQL, Laravel', $fields['technical_skills']);
    }

    public function test_ambiguous_rows_are_not_invented_as_employment(): void
    {
        [$fields] = (new CvProfileBuilder)->extract("WORK EXPERIENCE\nConsulting across multiple clients\nEDUCATION\nVarious courses");
        $this->assertSame([], $fields['employment_entries']);
        $this->assertSame([], $fields['education_entries']);
        $this->assertStringContainsString('Consulting across multiple clients', $fields['additional_information']);
    }
}
