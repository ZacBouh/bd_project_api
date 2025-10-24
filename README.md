# Backend

## Paiement — Idempotence

### Amont (création de session)
- Génération d’une `idempotency_key` déterministe côté back lorsque le front n’en fournit pas : `sha256(userId + copies triées)`.
- Les appels Stripe Checkout incluent toujours cette clé en deuxième paramètre.
- Les journaux précisent l’utilisateur, les copies et la clé pour aider au troubleshooting.

### Aval (webhooks)
- Table `stripe_event` avec contrainte d’unicité sur `event_id` utilisée pour ignorer les doublons.
- Traitement des évènements dans une transaction : enregistrement de l’évènement, création de la commande (`orders.checkout_session_id` unique), mise à jour des copies (`for_sale` à `false` seulement si elles étaient encore disponibles) et réservation de l’e-mail.
- Journal `checkout_session_email` pour n’envoyer la notification qu’une seule fois par session.
- Les logs notent les évènements reçus, les duplicatas ignorés et les mises à jour appliquées.

### Tests (Stripe CLI)
1. **Session Checkout idempotente**
   - `stripe checkout.sessions.create --idempotency-key <clé> ...` deux fois avec le même panier.
   - Vérifier que Stripe retourne la même session et que les logs indiquent la même clé.
2. **Webhook dupliqué**
   - `stripe events resend --event <event_id>` deux fois.
   - Une seule ligne attendue dans `stripe_event`, aucune duplication d’ordre ni d’e-mail.
3. **Contrôle observabilité**
   - Requête : `SELECT type, COUNT(*) FROM stripe_event GROUP BY 1 ORDER BY 2 DESC;` pour vérifier la volumétrie par type d’évènement.
