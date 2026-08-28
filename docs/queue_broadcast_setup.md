Configuration Queue & Broadcast

Queue (recommandé: Redis ou database)

1) .env example for database driver:

QUEUE_CONNECTION=database

2) Créer la table jobs si nécessaire:

```bash
php artisan queue:table
php artisan migrate
```

3) Lancer un worker:

```bash
php artisan queue:work --sleep=3 --tries=3
```

Broadcast (recommandé: Pusher ou Redis + laravel-echo-server)

1) Exemple .env pour Pusher:

BROADCAST_DRIVER=pusher
PUSHER_APP_ID=your_id
PUSHER_APP_KEY=your_key
PUSHER_APP_SECRET=your_secret
PUSHER_HOST=
PUSHER_PORT=443
PUSHER_SCHEME=https
PUSHER_APP_CLUSTER=mt1

2) Installer et configurer Laravel Echo côté frontend (npm):

```bash
npm install --save laravel-echo pusher-js
```

3) Exemple d'initialisation client (JS):

```js
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;
window.Echo = new Echo({
  broadcaster: 'pusher',
  key: process.env.MIX_PUSHER_APP_KEY,
  cluster: process.env.MIX_PUSHER_APP_CLUSTER,
  encrypted: true,
  auth: { headers: { Authorization: `Bearer ${TOKEN}` } }
});

window.Echo.private(`users.${USER_ID}`).listen('NotificationCreated', (e) => {
  console.log('notif', e);
});
```

4) Pour Redis + laravel-echo-server, configurer `BROADCAST_DRIVER=redis` et lancer `laravel-echo-server`.

Notes:
- Assurez-vous que `config/broadcasting.php` et `config/queue.php` sont correctement configurés pour vos drivers.
- En environnement de test local, vous pouvez laisser `QUEUE_CONNECTION=sync` et `BROADCAST_DRIVER=log` pour simplifier le debug.
