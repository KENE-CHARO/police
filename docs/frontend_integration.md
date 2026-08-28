Integration frontend - exemples d'appels

Remplacez `BASE_URL` et `TOKEN` par vos valeurs.

1) Auth - login (curl)

```bash
curl -X POST "BASE_URL/api/auth/login" \
  -H "Content-Type: application/json" \
  -d '{"email":"user@example.com","password":"secret"}'
```

Réponse attendue: JSON contenant le `token` (utiliser dans Authorization header).

2) Assigner une enquête (Axios)

```js
import axios from 'axios';
const client = axios.create({ baseURL: 'BASE_URL', headers: { Authorization: `Bearer ${TOKEN}` } });

await client.post('/api/enquetes/assign', { plainte_id: 1, enqueteur_id: 5 });
```

3) Mettre à jour le statut d'une enquête (curl)

```bash
curl -X PUT "BASE_URL/api/enquetes/1/status" \
  -H "Authorization: Bearer TOKEN" -H "Content-Type: application/json" \
  -d '{"statut":"in_progress"}'
```

4) Récupérer notifications (Axios)

```js
const res = await client.get('/api/notifications');
console.log(res.data);
```

5) Marquer une notification comme lue (curl)

```bash
curl -X POST "BASE_URL/api/notifications/10/read" -H "Authorization: Bearer TOKEN"
```

6) Télécharger une pièce jointe (curl)

```bash
curl -X GET "BASE_URL/api/plaintes/1/attachments/3" -H "Authorization: Bearer TOKEN" --output piece.jpg
```

Notes:
- Tous les endpoints protégés nécessitent le header `Authorization: Bearer <token>`.
- Pour intégrer avec Axios/fetch, configurez l'entête `Authorization` globalement.
