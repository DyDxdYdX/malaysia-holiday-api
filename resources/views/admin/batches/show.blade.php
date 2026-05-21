<x-layouts::app :title="__('Batch #:id', ['id' => $batch->id])">
    <livewire:admin.batch-show :batch="$batch" />
</x-layouts::app>
