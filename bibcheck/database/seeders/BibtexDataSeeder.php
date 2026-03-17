<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use App\Models\BibtexTypeEntry;
use App\Models\BibtexField;

class BibtexDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            'article'        => ["author", "title", "journal", "year", "pages", "volume", "number"],
            'book'           => ["author", "title", "year", "address", "publisher", "pagetotal"],
            'manual'         => ["organization", "title", "year"],
            'misc'           => ["author", "title", "urldate", "url"],
            'online'         => ["author", "title", "urldate", "url"],
            'mvbook'         => ["author", "title", "year", "address", "publisher", "pagetotal"],
            'inbook'         => ["author", "title", "booktitle", "year"],
            'bookinbook'     => ["author", "title", "booktitle", "year"],
            'suppbook'       => ["author", "title", "booktitle", "year"],
            'booklet'        => ["author", "title", "year"],
            'collection'     => ["editor", "title", "year"],
            'mvcollection'   => ["editor", "title", "year"],
            'incollection'   => ["author", "title", "booktitle", "year"],
            'suppcollection' => ["author", "title", "booktitle", "year"],
            'patent'         => ["author", "title", "number", "year"],
            'periodical'     => ["editor", "title", "year"],
            'suppperiodical' => ["author", "title", "journal", "year", "pages"],
            'proceedings'    => ["title", "year"],
            'mvproceedings'  => ["title", "year"],
            'inproceedings'  => ["author", "title", "booktitle", "year", "pages", "organization"],
            'reference'      => ["editor", "title", "year"],
            'mvreference'    => ["editor", "title", "year"],
            'inreference'    => ["author", "title", "booktitle", "year"],
            'report'         => ["author", "title", "type", "institution", "year"],
            'thesis'         => ["author", "title", "type", "institution", "year"],
            'unpublished'    => ["author", "title", "year"],
            'mastersthesis'  => ["author", "title", "institution", "year"],
            'techreport'     => ["author", "title", "institution", "year"],
            'conference'     => ["author", "title", "booktitle", "year", "pages", "organization"],
            'electronic'     => ["author", "title", "urldate", "url"],
            'phdthesis'      => ["author", "title", "institution", "year"],
            'www'            => ["author", "title", "urldate", "url"],
            'school'         => ["author", "title", "institution", "year"],
        ];


        foreach ($data as $typeName => $fields) {
            $typeEntry = BibtexTypeEntry::firstOrCreate(['name_type_entry' => $typeName]);

            foreach ($fields as $index => $field) {
                $field = BibtexField::firstOrCreate(['name_field' => $field]);
                $typeEntry->fields()->syncWithoutDetaching([
                    $field->id, ['sort_order' => $index]
                ]);
            }
        }
    }
}
