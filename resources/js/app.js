import './bootstrap';
import { Livewire } from '../../vendor/livewire/livewire/dist/livewire.esm';
import focus from '@alpinejs/focus';
import { registerSantaCruzSyncAlpine } from './santaCruzSync';

Alpine.plugin(focus);

registerSantaCruzSyncAlpine();

Livewire.start();
