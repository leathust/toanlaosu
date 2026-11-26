<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import StudentLayout from '../../Layouts/StudentLayout.vue';

interface LessonSummary {
    slug: string;
    title: string;
    unit: string;
}

interface CourseSummary {
    slug: string;
    title: string;
    summary: string | null;
    difficulty: string;
    period: number;
    progress: number;
    lessons: LessonSummary[];
}

defineProps<{
    student: { name: string };
    courses: CourseSummary[];
}>();
</script>

<template>
    <StudentLayout>
        <div class="grid gap-8">
            <header class="grid gap-3">
                <p class="text-sm font-semibold uppercase tracking-[0.18em] text-brand-700">Không gian học tập</p>
                <h1 class="text-3xl font-semibold tracking-tight text-slate-950">Chào {{ student.name }}</h1>
                <p class="max-w-2xl text-sm leading-6 text-slate-600">Tiếp tục từ đúng đợt học và trạng thái tiến độ đã lưu trên máy chủ.</p>
            </header>

            <section v-if="courses.length" class="grid gap-5 lg:grid-cols-2">
                <article v-for="course in courses" :key="course.slug + course.period" class="grid gap-5 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="flex items-start justify-between gap-4">
                        <div class="grid gap-2">
                            <span class="text-xs font-semibold uppercase tracking-wide text-brand-700">Đợt {{ course.period }} · {{ course.difficulty }}</span>
                            <h2 class="text-xl font-semibold text-slate-950">{{ course.title }}</h2>
                            <p class="text-sm leading-6 text-slate-600">{{ course.summary || 'Khóa học đang được cập nhật.' }}</p>
                        </div>
                        <span class="text-2xl font-semibold text-brand-700">{{ course.progress }}%</span>
                    </div>
                    <div class="grid gap-2">
                        <div class="h-2 overflow-hidden rounded-full bg-slate-100"><div class="h-full rounded-full bg-brand-600" :style="{ width: `${course.progress}%` }" /></div>
                        <p class="text-xs text-slate-500">{{ course.lessons.length }} bài học trong khóa</p>
                    </div>
                    <div class="grid gap-2">
                        <Link v-for="lesson in course.lessons" :key="lesson.slug" :href="`/student/learn/courses/${course.slug}/lessons/${lesson.slug}`" class="flex items-center justify-between rounded-2xl border border-slate-200 px-4 py-3 text-sm hover:border-brand-300 hover:bg-brand-50">
                            <span><span class="block text-xs text-slate-500">{{ lesson.unit }}</span><span class="font-semibold text-slate-800">{{ lesson.title }}</span></span>
                            <span aria-hidden="true" class="text-brand-600">→</span>
                        </Link>
                    </div>
                </article>
            </section>
            <div v-else class="rounded-3xl border border-dashed border-slate-300 bg-white p-10 text-center text-sm text-slate-600">Bạn chưa có khóa học đang hoạt động.</div>
        </div>
    </StudentLayout>
</template>
