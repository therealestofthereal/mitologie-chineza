# MITOLOGIE CHINEZĂ

## AUTOR
- URSULESCU FLORIN-ALEXANDRU

## UNITATEA DE ÎNVĂȚĂMÂNT
- LICEUL ATANASIE MARIENESCU LIPOVA

## PROFESOR COORDONATOR
- CRIHAN RONELA

## DESCRIERE GENERALĂ
Mitologie Chineză este un website educațional creat pentru a oferi informații despre mitologia chineză în mod interactiv. Proiectul combină pagini cu conținut, un sistem de comentarii cu răspunsuri și like-uri, administrare de comentarii, autentificare utilizator și un quiz cu salvare a scorului.

## FUNCȚIONALITĂȚI
- Autentificare și înregistrare utilizatori
- Profil de utilizator cu upload avatar
- Sistem de comentarii cu răspunsuri (threaded comments)
- Like / unlike comentarii
- Moderator / admin pentru gestionarea comentariilor
- Quiz educațional cu salvare a celui mai bun scor
- Protecție CSRF și securizare prin sesiuni
- Fallback de avatar din baza de date pentru mediul Railway

## ARHITECTURĂ TEHNICĂ
- Backend: PHP 8.2
- Frontend: HTML, CSS, JavaScript vanilla
- Bază de date: MySQL
- Deploy: Railway + Docker
- Conexiune DB: `db_config.php` citește variabila de mediu `DATABASE_URL`

## COMPOZIȚIA PROIECTULUI
- `db_config.php` - configurare și conexiune la MySQL
- `csrf.php` - generare și validare token CSRF
- `process_auth.php` - login și înregistrare
- `submit_comment.php` - salvare comentarii și reply-uri
- `display_comments.php` - afișare comentarii și thread-uri
- `comment_like.php` - like / unlike comentarii
- `admin_comments.php` - interfață admin pentru moderare
- `upload_avatar.php` - upload avatar și salvare în DB
- `avatar.php` - servire avatar din fișier sau din blob DB
- `save_score.php` - salvare scor quiz
- `session_check.php` - verificare sesiune și răspuns JSON
- `site.js` - logica client-side comună și AJAX
- `Dockerfile` - containerizare PHP pentru deploy

## SCHEMA BAZEI DE DATE
Tabele principale:
- `site_users` (utilizatori, parole hash-uite, rol, avatar, highscore)
- `messages` (comentarii, `parent_id` pentru reply-uri, `page`, `user_id`)
- `comment_likes` (like-uri legate de comentarii și utilizatori)

## SECURITATE
- protecție CSRF în formulare și request-uri AJAX
- utilizare `PDO::prepare()` pentru query-uri SQL parametrizate
- parole stocate hash-uite cu `password_hash()`
- validare input în backend
- `htmlspecialchars()` la afișare pentru a evita XSS
- acces admin verificat pe rol în sesiune

## DEPLOY RAILWAY
- proiectul rulează în Railway folosind `Dockerfile`
- serverul PHP intern pornește cu `php -S 0.0.0.0:8080`
- variabila de mediu `DATABASE_URL` trebuie setată în Railway
- fișierele de tip upload pot fi efemere, motiv pentru care avatarul este servit și din BLOB în DB

## RULARE LOCALĂ
1. Clonează proiectul
2. Construiește Docker:
   ```bash
   docker build -t mitologie-chineza .
   ```
3. Rulează containerul:
   ```bash
   docker run -p 8080:8080 -e DATABASE_URL='mysql://user:pass@host:3306/dbname' mitologie-chineza
   ```
4. Deschide `http://localhost:8080`

## RESURSE EXTERNE
- Proiectul nu folosește framework frontend extern sau CMS.
- Codul aplicației este scris de autor.
- Pentru unele imagini folosite în pagină s-au utilizat resurse publice sau gratuite.
- Structura infoboxurilor și prezentarea unor fișe informative au fost inspirate din stilul Wikipedia, dar implementarea aplicației și logica rămân realizate de autor.
- Pentru anumite funcționalități s-au consultat tutoriale și documentație online ca punct de plecare, dar soluțiile au fost adaptate și implementate personal.

## ASISTENȚĂ AI
- Autorul a folosit un asistent AI pentru pregătirea deploy-ului pe Railway și pentru debugging legat de rularea site-ului pe Railway.
- O parte din textul paginilor a fost generată cu ajutorul AI.
- AI a contribuit și la soluția de gestionare a avatarurilor pe Railway, în timp ce funcționalitatea a fost testată și creată local de autor.
- Implementarea principală, logica aplicației și structura de bază rămân responsabilitatea autorului.

## NOTĂ
Acest proiect a fost dezvoltat pentru secțiunea Web a concursului InfoEducație și reprezintă o implementare proprie a unei aplicații educative bazate pe mitologie chineză.

## DECLARAȚIE
Autorul declară că proiectul reprezintă o creație proprie și că toate resursele externe folosite sunt menționate în prezentul document.
