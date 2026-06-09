<?php

namespace App\Imports;

use App\Models\Course;
use App\Models\Category;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class CoursesImport implements ToModel, WithHeadingRow
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        // Skip if title is empty
        if (empty($row['title']) || empty($row['judul'])) {
            $title = $row['title'] ?? $row['judul'] ?? null;
            if (!$title) return null;
        } else {
            $title = $row['title'] ?? $row['judul'];
        }

        // Find or create category if provided
        $categoryId = null;
        $categoryName = $row['category'] ?? $row['kategori'] ?? null;
        if ($categoryName) {
            $category = Category::firstOrCreate([
                'name' => $categoryName
            ], [
                'slug' => Str::slug($categoryName)
            ]);
            $categoryId = $category->id;
        }

        // Map status
        $status = strtolower($row['status'] ?? 'draft');
        if (!in_array($status, ['draft', 'published'])) {
            $status = 'draft';
        }

        // Intercept published status for instructors if course moderation is enabled
        if (auth()->check() && !auth()->user()->isAdmin()) {
            $moderationEnabled = \App\Models\Setting::where('key', 'instructor_course_moderation')->value('value');
            $isModerated = ($moderationEnabled === 'true' || $moderationEnabled === '1' || $moderationEnabled === true || $moderationEnabled === 1);
            if ($isModerated && $status === 'published') {
                $status = 'pending';
            }
        }

        // Map level
        $level = $row['level'] ?? $row['tingkatan'] ?? 'Umum';
        $validLevels = ['SD', 'SMP', 'SMA', 'Umum'];
        // Case-insensitive match for level
        $matchedLevel = 'Umum';
        foreach ($validLevels as $vl) {
            if (strtolower($vl) === strtolower($level)) {
                $matchedLevel = $vl;
                break;
            }
        }

        return new Course([
            'instructor_id' => auth()->id() ?? 1, // fallback to 1 if not logged in (e.g. CLI), but should be logged in
            'title'         => $title,
            'slug'          => Str::slug($title) . '-' . uniqid(),
            'category_id'   => $categoryId,
            'price'         => floatval($row['price'] ?? $row['harga'] ?? 0),
            'level'         => $matchedLevel,
            'capacity'      => intval($row['capacity'] ?? $row['kapasitas'] ?? 20),
            'status'        => $status,
            'description'   => $row['description'] ?? $row['deskripsi'] ?? null,
            'about'         => $row['about'] ?? $row['tentang'] ?? null,
            'bg_color'      => $row['bg_color'] ?? $row['warna_latar'] ?? '#44A6D9',
            'icon_type'     => $row['icon_type'] ?? $row['ikon'] ?? 'code',
        ]);
    }
}
