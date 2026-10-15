<?php
namespace App\Services\Talent;

/** Canonical CV form definitions; all values remain unverified until approval. */
final class CvFieldSchema
{
    public const GROUPS = [
        'Contact information' => [
            'first_name'=>'First name','last_name'=>'Last name','email'=>'Professional email address',
            'phone'=>'Mobile phone number','location'=>'City/town and region',
            'linkedin_url'=>'LinkedIn profile URL','portfolio_url'=>'Online portfolio / GitHub URL',
        ],
        'Personal profile / summary' => [
            'professional_title'=>'Current professional title','years_experience'=>'Years of experience hook',
            'expertise'=>'Core areas of expertise','achievement_headline'=>'Key achievement headline',
            'career_objective'=>'Value proposition / career objective',
        ],
        'Skills inventory' => [
            'technical_skills'=>'Hard / technical skills','software_tools'=>'Software, tools, CRM and CMS',
            'programming'=>'Programming languages and frameworks','soft_skills'=>'Soft skills',
            'methodologies'=>'Methodologies (Agile, Scrum, Six Sigma, etc.)',
        ],
        'Certifications & training' => [
            'professional_memberships'=>'Professional bodies and memberships',
            'industry_certifications'=>'Industry certifications','licences'=>'Relevant licences',
            'short_courses'=>'Short courses and workshops',
        ],
        'Additional sections' => [
            'languages'=>'Languages and proficiency levels','volunteering'=>'Volunteering roles and organisations',
            'publications'=>'Publications, research and patents','speaking'=>'Speaking engagements and conferences',
            'awards'=>'Industry awards and recognition','interests'=>'Relevant hobbies and interests',
            'additional_information'=>'Other verified information',
        ],
    ];

    public const REPEATABLE = [
        'employment_entries' => ['label'=>'Work experience / career history','fields'=>[
            'company'=>'Company / organisation','title'=>'Job title / role','location'=>'Employment location',
            'start'=>'Start month and year (YYYY-MM)','end'=>'End month and year (YYYY-MM, or blank if present)',
            'present'=>'Currently employed here (yes/no)','duties'=>'Core duties / action-led bullet points',
            'achievements'=>'Quantifiable achievements (verified metrics)',
            'projects'=>'Projects managed or contributed to','tools'=>'Role-specific tools and technologies',
            'progression'=>'Promotions / progression within this organisation',
        ]],
        'education_entries' => ['label'=>'Education and qualifications','fields'=>[
            'qualification'=>'Degree / qualification title','institution'=>'Institution / university',
            'location'=>'Institution location','graduation'=>'Graduation / expected month and year (YYYY-MM)',
            'expected'=>'Expected graduation (yes/no)','grade'=>'Classification / grade',
            'coursework'=>'Relevant modules / coursework','thesis'=>'Dissertation / thesis',
            'college'=>'A-levels / college diplomas with subjects and grades',
            'school'=>'GCSEs / secondary-school subjects and grades',
        ]],
    ];

    public const SETTINGS = [
        'reverse_chronological'=>'Reverse-chronological order checked',
        'proofread'=>'Grammar, spelling and typos reviewed',
        'ats_font'=>'ATS-friendly font',
        'consistent_dates'=>'Consistent dates reviewed',
        'file_format'=>'Export format',
        'filename_format'=>'CV file name format',
        'reference_placeholder'=>'Include references available on request',
    ];

    public static function defaults(): array
    {
        $data = [];
        foreach (self::GROUPS as $fields) foreach ($fields as $key=>$label) $data[$key]='';
        foreach (self::REPEATABLE as $key=>$group) $data[$key]=[];
        foreach (self::SETTINGS as $key=>$label) $data[$key]='';
        $data['ats_font']='DejaVu Sans';
        $data['file_format']='pdf';
        $data['filename_format']='first_last_cv';
        return $data;
    }
}
