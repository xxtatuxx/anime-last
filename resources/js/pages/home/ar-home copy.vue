<script setup lang="ts">
import {
  Table,
  TableBody,
  TableCaption,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import AppLayout from '@/layouts/AR-HomeLayout.vue';
import { Post, type BreadcrumbItem } from '@/types';
import { Head, Link, usePage, router as inertia } from '@inertiajs/vue3';
import { ref, watch, onMounted } from 'vue';
import { toast } from 'vue-sonner'
import { Input } from '@/components/ui/input'
import Fuse from 'fuse.js'
import { useAuth } from '@/composables/useAuth'


</script>

<template>
  <Head title="moves + series" />
  <AppLayout>

  <table>
  
   <div class="relative min-h-[100vh] flex-1 rounded-xl border border-sidebar-border/70 dark:border-sidebar-border md:min-h-min">
        <Table>
          <TableCaption>A list of your recent posts.</TableCaption>
          <TableHeader>
            <TableRow>
              <TableHead class="w-[100px]"></TableHead>
              <TableHead></TableHead>
              <TableHead></TableHead>
              <TableHead class="text-right"></TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            <TableRow>
              <TableCell class="font-medium"></TableCell>
              <TableCell></TableCell>
              <TableCell>
              </TableCell>
              <TableCell class="flex justify-end gap-2">
              
              </TableCell>
            </TableRow>

            <TableRow >
              <TableCell colspan="4" class="py-4 text-center text-gray-500">
                No posts match your search
              </TableCell>
            </TableRow>
          </TableBody>
        </Table>
      </div>
  </table>
   
  </AppLayout>
</template>
