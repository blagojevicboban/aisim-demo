# Uputstvo za podešavanje i održavanje AISim demo sajta

**Domen:** `https://aisim.tsp.edu.rs/`  
**GitHub repozitorijum:** `https://github.com/blagojevicboban/aisim-demo`  
**Port servisa za webhook:** `9012`  
**Tajna šifra (Webhook Secret):** `aisim-demo-secret-2026`

---

## 1. Kloniranje repozitorijuma na serveru

Prijavite se na server preko SSH (kao korisnik domena, npr. `aisim` ili `root`):

```bash
cd /home/aisim
git clone https://github.com/blagojevicboban/aisim-demo.git demo-repo
cd /home/aisim/demo-repo
chmod +x deploy/deploy.sh deploy/webhook-server.js
```

---

## 2. Inicijalno postavljanje Moodle kurseva i podešavanja

Kopirajte datoteke u Moodle direktorijum i pokrenite inicijalni setup:

```bash
cp demo.php /home/aisim/public_html/moodle/
cp aisim-demo.mbz /home/aisim/public_html/moodle/
cp aisim-demo-sr.mbz /home/aisim/public_html/moodle/
cp aisim-demo-sk.mbz /home/aisim/public_html/moodle/
cp aisim-demo-pt.mbz /home/aisim/public_html/moodle/

cd /home/aisim/public_html/moodle
php demo.php setup
```

Ova komanda automatski:
- Uključuje filter za veštačku inteligenciju (`filter_aisim`).
- Instalira srpske (`sr_lt`, `sr_cr`), slovačke (`sk`) i portugalske (`pt`) jezičke pakete.
- Kreira moderne tematske indigo Boost stilove i prilagođenu naslovnu stranu sa živim optimizatorom.
- Restaurira sva 4 kursa: **`AISIM-DEMO`** (engleski), **`AISIM-DEMO-SR`** (srpski), **`AISIM-DEMO-SK`** (slovački) i **`AISIM-DEMO-PT`** (portugalski).
- Kreira i upisuje demo naloge (`student` i `teacher`, lozinka `Demo-2026`).

---

## 3. Pokretanje Webhook servisa preko PM2

U fascikli repozitorijuma pokrenite PM2 proces:

```bash
cd /home/aisim/demo-repo
pm2 start deploy/ecosystem.config.js
pm2 save
```

Proverite status:
```bash
pm2 status aisim-webhook
curl http://127.0.0.1:9012/health
# Treba da vrati: ok
```

---

## 4. Virtualmin / Apache ProxyPass konfiguracija

U Virtualmin panelu za domen `aisim.tsp.edu.rs`:
1. Otvorite **Server Configuration > Edit Webhead / Apache Directives**.
2. Dodajte sledeća dva reda unutar `<VirtualHost>` bloka (ispred PHP proxy sekcije):

```apache
ProxyPass /webhook http://127.0.0.1:9012/webhook
ProxyPassReverse /webhook http://127.0.0.1:9012/webhook
```

3. Kliknite na **Save and Restart Apache**.

---

## 5. Podešavanje GitHub Webhook-a

1. U GitHub repozitorijumu `blagojevicboban/aisim-demo` idite na **Settings > Webhooks > Add webhook**.
2. **Payload URL:** `https://aisim.tsp.edu.rs/webhook`
3. **Content type:** `application/json`
4. **Secret:** `aisim-demo-secret-2026`
5. **Which events would you like to trigger this webhook?** `Just the push event`
6. Sačuvajte (**Add webhook**).
7. Proverite u kartici **Recent Deliveries** da je ping uspešno prošao sa zelenom kvačicom (HTTP 200 `pong`).

---

## 6. Noćno automatsko resetovanje (Cron)

Kako bi se svake noći obrisali pokušaji rešavanja kvizova i postovi na forumu, dodajte u crontab korisnika (`crontab -e`):

```cron
30 3 * * * /usr/bin/php /home/aisim/public_html/moodle/demo.php reset > /dev/null 2>&1
```
