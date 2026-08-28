# دليل تشغيل ATHAR مع CAINE 14.0 خطوة بخطوة

> **هدف هذا الدليل:** تشغيل منصة ATHAR على جهاز المضيف، ثم ربطها بآلة CAINE 14.0 داخل VirtualBox، ثم تنفيذ أول تجربة حقيقية آمنة باستخدام `tshark.packet_summary` وملف PCAP تجريبي فقط.

> **قاعدة السلامة:** لا تستخدم في هذه المرحلة أي دليل حقيقي أو ملف من قضية فعلية. استخدم PCAP تعليميًا/تجريبيًا لا يحتوي على بيانات حساسة. لا ترسل رمز الـAgent أو كلمات مرور MySQL أو محتويات ملف `.env` في المحادثة أو في GitHub.

## خريطة العمل قبل البدء

ستعمل بين بيئتين منفصلتين. لا تضع CAINE داخل مشروع Laravel ولا تشغّل أدوات CAINE من داخل المتصفح.

| البيئة | دورها | لا تفعل فيها |
|---|---|---|
| **جهاز المضيف** | Laravel، MySQL، Redis، لوحة ATHAR، التخزين الخاص، Queue، Scheduler | لا تضع أدلة داخل `public/` ولا تحفظ الأسرار في Git. |
| **CAINE داخل VirtualBox** | Forensic Agent وأداة TShark فقط في التجربة الأولى | لا تفتح منفذًا عامًا نحو CAINE، ولا تشغّل أي أمر Shell حر من Job. |

نفّذ الخطوات بالترتيب. بعد كل خطوة تحقق من النتيجة المتوقعة قبل الانتقال للتي تليها.

---

## الخطوة 1: تنزيل آخر نسخة من المشروع

افتح Terminal أو PowerShell على **جهاز المضيف**، ثم انتقل إلى المجلد الذي فيه مشروع ATHAR. إن لم تكن قد نزّلت المشروع من قبل، نفّذ `git clone` مرة واحدة. إن كان موجودًا لديك، استخدم `git pull` فقط.

```bash
# نفّذ هذا مرة واحدة فقط إذا لم يكن المشروع موجودًا عندك
git clone https://github.com/ko456123mo-jpg/ATHAR.git

# بعد ذلك، وفي كل مرة تريد تحديث المشروع
cd ATHAR
git pull origin main
```

يجب أن ترى في النهاية رسالة تتضمن أحدث commit. لا تنسخ إلى هنا أي رسالة تحتوي على بيانات اعتماد GitHub.

---

## الخطوة 2: التأكد من المتطلبات على جهاز المضيف

داخل مجلد `ATHAR`، نفّذ الأوامر التالية. إذا ظهر رقم إصدار لكل أمر، فالمتطلب موجود. إن ظهر أن أمرًا غير معروف، ثبّت هذا البرنامج أولًا ثم أعد المحاولة.

```bash
php -v
composer --version
node --version
npm --version
mysql --version
redis-cli ping
```

النتائج المهمة هي PHP 8.3 أو أحدث، Node 22 أو أحدث، و`PONG` من Redis. إذا كان Redis لا يعمل، شغّله من مدير خدمات نظامك قبل المتابعة. لا تبدأ إعداد CAINE قبل أن ينجح Redis.

---

## الخطوة 3: إنشاء قاعدة بيانات MySQL ومستخدم محدود الصلاحية

افتح MySQL بمستخدم إداري محلي. في بيئة تطوير محلية فقط يمكنك استخدام مستخدم MySQL الإداري لإنشاء قاعدة البيانات والحساب، ثم اخرج من MySQL. استبدل النص بين الأقواس بقيمك أنت؛ لا تكتب كلمة المرور هنا.

```sql
CREATE DATABASE athar CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'athar_user'@'localhost' IDENTIFIED BY 'ضع-كلمة-مرور-قوية-هنا';
GRANT ALL PRIVILEGES ON athar.* TO 'athar_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

> لا تستخدم حساب `root` من Laravel. حساب `athar_user` مخصص للتطبيق فقط. في الإنتاج الفعلي يجب أيضًا تقييد الوصول الشبكي وإدارة النسخ الاحتياطية بصورة منفصلة.

---

## الخطوة 4: إنشاء ملف إعداد ATHAR المحلي

داخل مجلد ATHAR، انسخ قالب الإعداد إلى `.env`. افتح الملف بمحرر نصوص، ولا ترفعه إلى GitHub.

```bash
cp .env.example .env
```

عدّل القيم التالية داخل `.env` فقط. المثال التالي يوضّح الشكل؛ أبقِ كلمة مرور قاعدة البيانات الحقيقية في ملفك المحلي ولا تنشرها.

```dotenv
APP_NAME=ATHAR
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=athar
DB_USERNAME=athar_user
DB_PASSWORD=<كلمة-مرور-محلية-سرية>

QUEUE_CONNECTION=redis
CACHE_STORE=redis
SESSION_DRIVER=database
FILESYSTEM_DISK=private
ATHAR_EVIDENCE_DISK=private
ATHAR_MAX_EVIDENCE_UPLOAD_KILOBYTES=20480
ATHAR_AGENT_RESULT_LIMIT_BYTES=10485760
```

لا تحتاج إلى وضع `ATHAR_AGENT_TOKEN` هنا؛ هذا الرمز يخص CAINE وحده وسيُحفظ هناك لاحقًا.

---

## الخطوة 5: تثبيت مكتبات Laravel وبناء الواجهة

ما زلت داخل مجلد ATHAR على جهاز المضيف، نفّذ الأوامر التالية بالتتابع. عند وجود ملف `vendor/` أو `node_modules/` قد يستغرق بعضها وقتًا أقل في المرات التالية.

```bash
composer install
npm install
php artisan key:generate
npm run build
```

النتيجة المتوقعة هي ظهور رسالة نجاح لتوليد `APP_KEY` ورسالة نجاح من Vite build. لا تنشر قيمة `APP_KEY` أو محتوى `.env`.

---

## الخطوة 6: إنشاء جداول ATHAR وسجل الأدوات

نفّذ migration والـseed مرة واحدة على قاعدة بيانات التطوير الجديدة.

```bash
php artisan migrate --seed
```

هذا ينشئ، من ضمن أمور أخرى، جداول القضايا والأدلة وبصمات SHA-256 وسلسلة الحيازة والـJobs والـAgents والتقارير والتدقيق. كما يضيف كتالوج الأقسام العشرة للأدوات، مع سجل TShark الأولي.

> ظهور TShark في الكتالوج **لا يعني** أن ATHAR أثبت تشغيله داخل CAINE. الحالة لا تُرفع إلى `end_to_end_verified` إلا بعد إكمال الخطوات الواقعية في هذا الدليل.

---

## الخطوة 7: تشغيل خدمات ATHAR الثلاث

افتح **ثلاث نوافذ Terminal منفصلة** على جهاز المضيف. في كل نافذة انتقل إلى مجلد ATHAR أولًا.

### النافذة الأولى: واجهة Laravel

```bash
cd ATHAR
php artisan serve --host=127.0.0.1 --port=8000
```

افتح المتصفح على `http://127.0.0.1:8000`. يجب أن تظهر صفحة ATHAR. أنشئ حسابًا محليًا للتطوير. الحساب المسجل ذاتيًا يحصل افتراضيًا على دور Investigator.

### النافذة الثانية: عامل Queue

```bash
cd ATHAR
php artisan queue:work redis --tries=1 --timeout=360
```

اترك هذه النافذة مفتوحة. هذا العامل ينقل مهمة التحليل من حالة `created` إلى `queued`. لا يغني عن CAINE ولا يشغّل TShark بنفسه.

### النافذة الثالثة: Laravel Scheduler

```bash
cd ATHAR
php artisan schedule:work
```

اتركها مفتوحة. هذا يشغّل فحص انتهاء المهام كل دقيقة. إذا توقفت مهمة بعدما يطالب بها Agent أو يبدأها، يسجل ATHAR انتهاء المهلة كـ`timed_out` مع Audit وسلسلة حيازة.

---

## الخطوة 8: إنشاء مستخدم Administrator للتجربة

يلزم Administrator لتسجيل Agent أو تدوير رمزه. لأن الحساب المسجل من الواجهة يبدأ Investigator، استخدم MySQL محليًا لتغيير دور حسابك التجريبي **مرة واحدة فقط**. استبدل البريد الإلكتروني ببريدك التجريبي الذي سجلته في ATHAR.

```sql
USE athar;
UPDATE users
SET role = 'administrator'
WHERE email = 'your-test-email@example.test';
```

سجّل الخروج ثم سجّل الدخول مرة أخرى. لا تغير دور أي حساب حقيقي في بيئة الإنتاج بهذه الطريقة؛ لاحقًا سنضيف إدارة مستخدمين إدارية صريحة.

---

## الخطوة 9: إعداد شبكة VirtualBox المعزولة

لا تستخدم وضع **Bridged** ولا تفتح منافذ CAINE للإنترنت في هذه التجربة. اختر شبكة تربط CAINE بجهاز المضيف فقط.

1. أوقف CAINE من VirtualBox إن كانت تعمل.
2. افتح **VirtualBox → Settings → Network** للآلة CAINE.
3. في Adapter 1 اختر **Host-only Adapter**. إن لم يظهر Adapter، أنشئ واحدًا من **Tools → Network Manager → Host-only Networks** أولًا.
4. فعّل خيار **Cable Connected**.
5. شغّل CAINE.
6. داخل CAINE، نفّذ:

```bash
ip addr
ip route
```

سجّل عنوان CAINE وعنوان واجهة Host-only على جهاز المضيف. لا ترسل عناوين عامة أو بيانات شبكية حساسة؛ يكفي إعلامي لاحقًا إن كان الاتصال بينهما يعمل أم لا.

في هذه المرحلة لا تعمل واجهة Laravel الافتراضية عبر HTTPS. يجب وضع خادم ويب TLS موثوق أمام Laravel على عنوان الـHost-only قبل تشغيل Agent. لا تستخدم `--allow-insecure-localhost` بين المضيف وCAINE.

---

## الخطوة 10: توفير HTTPS بين المضيف وCAINE

هذه الخطوة ضرورية لأن Agent يقبل HTTPS للشبكات غير المحلية. استخدم اسمًا داخليًا ثابتًا أو عنوان Host-only مع شهادة تحتوي الاسم أو العنوان ضمن SAN، ثم اجعل CAINE تثق بجهة إصدار الشهادة.

| المطلوب | النتيجة التي يجب أن تتحقق |
|---|---|
| خادم ويب TLS أمام Laravel | رابط مثل `https://athar-host.internal` أو `https://<host-only-ip>` يعمل من CAINE. |
| شهادة موثوقة داخل CAINE | لا يظهر خطأ certificate عند فتح الرابط من CAINE. |
| منفذ مفتوح على واجهة Host-only فقط | CAINE تصل إلى ATHAR، لكن لا يوجد منفذ وارد إلى CAINE. |

بعد إعداد TLS، من داخل CAINE اختبر الوصول دون Agent أولًا:

```bash
curl -I https://<عنوان-ATHAR-الداخلي>
```

توقف هنا إذا فشل TLS أو ظهر تحذير شهادة. لا تتجاوز الخطأ بخيار `-k`، ولا تنتقل إلى خطوة الرمز قبل أن يصبح الاتصال موثوقًا.

---

## الخطوة 11: مشاركة تخزين الأدلة الخاص كقراءة فقط

الهدف هو أن يقرأ CAINE **نسخة العمل** فقط. يمتلك ATHAR المسار المضيف التالي:

```text
ATHAR/storage/app/private
```

في VirtualBox، افتح **Settings → Shared Folders** للآلة CAINE وأضف هذا المسار من جهاز المضيف. استخدم اسم مشاركة `athar_private` وفعل خيار **Read-only**. لا تفعل Auto-mount إذا كنت تريد التحكم الكامل في نقطة التركيب.

بعد تشغيل CAINE، نفّذ داخله:

```bash
sudo mkdir -p /mnt/athar-private
sudo mount -t vboxsf -o ro athar_private /mnt/athar-private
findmnt -no TARGET,OPTIONS /mnt/athar-private
```

يجب أن ترى `/mnt/athar-private` وخيار `ro`. هذه النتيجة مهمة: لا نكمل إذا كان المسار قابلًا للكتابة.

---

## الخطوة 12: تسجيل CAINE Agent وإصدار رمز لمرة واحدة

من جهاز المضيف وفي مجلد ATHAR، وباستخدام حساب نظام يملك الوصول إلى المشروع، أصدر رمز Agent. استبدل `<caine-hostname>` باسم الجهاز الذي يظهر من الأمر `hostname` داخل CAINE.

```bash
php artisan athar:agent-token caine-vbox-01 --hostname=<caine-hostname>
```

سيظهر رمز طويل مرة واحدة. انسخه مباشرة إلى ملف محلي داخل CAINE، ثم اعتبره سرًا. لا تلتقط له صورة، لا تضعه في ملف المشروع، ولا ترسله إليّ. إذا تسرب الرمز، دوّره من API الإدارية أو أعد إصدار رمز جديد.

---

## الخطوة 13: نسخ Agent إلى CAINE وإعداد متغيراته

انسخ مجلد `ATHAR/agent` من المشروع إلى CAINE في المسار `/opt/athar-agent`. يمكنك استخدام وسيلة نقل موثوقة داخل الشبكة المعزولة أو مشاركة مؤقتة لا تحتوي رمزًا. بعد النسخ داخل CAINE:

```bash
cd /opt/athar-agent
python3 --version
```

أنشئ ملفًا محليًا مثل `~/.config/athar-agent.env` وضع فيه ما يلي. غيّر فقط عنوان ATHAR والرمز. لا تضف هذا الملف إلى Git.

```dotenv
ATHAR_BASE_URL=https://<عنوان-ATHAR-الداخلي>
ATHAR_AGENT_TOKEN=<الرمز-الذي-ظهر-مرة-واحدة>
ATHAR_AGENT_NAME=caine-vbox-01
ATHAR_REQUEST_TIMEOUT_SECONDS=20
ATHAR_EXECUTION_TIMEOUT_SECONDS=300
ATHAR_MAX_OUTPUT_BYTES=10485760
ATHAR_EVIDENCE_ROOT=/mnt/athar-private
```

اضبط أذونات الملف ثم حمّله في الطرفية الحالية:

```bash
chmod 600 ~/.config/athar-agent.env
set -a
source ~/.config/athar-agent.env
set +a
```

---

## الخطوة 14: التحقق من TShark داخل CAINE

لا تبدأ Agent بعد. تحقق أولًا من وجود أداة TShark وإصدارها:

```bash
/usr/bin/tshark --version
```

احتفظ بأول سطر من الناتج فقط. يجب أن يشير إلى TShark وتظهر نسخة. وجود المسار وحده دليل **path observed** أو **installed** حسب مراجعتك، لكنه ليس تحققًا من التنفيذ داخل ATHAR بعد.

---

## الخطوة 15: إنشاء تجربة PCAP داخل ATHAR

من متصفح جهاز المضيف:

1. سجّل الدخول إلى ATHAR بحساب Investigator أو Administrator مخصص للتطوير.
2. افتح **القضايا** ثم أنشئ قضية تجريبية باسم واضح، مثل `TShark POC — Benign PCAP`.
3. افتح **الأدلة** وارفع ملف PCAP أو PCAPNG تجريبي فقط. لا ترفع أي دليل حقيقي.
4. بعد رفعه، اضغط **نسخة عمل**. يجب أن تظهر عبارة تدل على أن نسخة العمل متاحة للتحليل.
5. افتح **مهام التحليل**، ثم اختر القضية نفسها والدليل ذي نسخة العمل وأداة TShark وعملية `packet_summary`.
6. أنشئ Job.

إذا لم يظهر الدليل في قائمة Jobs، تأكد أنك أنشأت Working Copy وأن عامل Queue يعمل. ATHAR يرفض إنشاء Job بلا نسخة عمل، ولا يرسل المفتاح الأصلي للـAgent.

---

## الخطوة 16: تشغيل دورة Agent واحدة داخل CAINE

في CAINE، بعد تحميل متغيرات البيئة، شغّل:

```bash
cd /opt/athar-agent
python3 -m athar_agent.client --once
echo $?
```

ماذا يفعل هذا الأمر؟ يبدأ Heartbeat صادرًا إلى ATHAR، يطالب بمهمة واحدة، يرسل حالة `running`، يشغل TShark باستخدام قائمة وسائط ثابتة وليس Shell، يحلل CSV، ويرسل النتيجة وبيانات الإصدار إلى ATHAR. ثم يتوقف عمدًا؛ لا يوجد loop دائم في POC.

| النتيجة | معناها | الإجراء التالي |
|---|---|---|
| `0` | لا توجد Job مؤهلة أو اكتملت دورة واحدة بنجاح | افحص لوحة ATHAR وJob ID. |
| `1` | مشكلة سياسة/مسار/HTTPS/TShark/نتيجة | لا تغير allowlist؛ اجمع الخطأ بعد إخفاء الأسرار وأرسله لي. |
| خطأ شهادة TLS | CAINE لا تثق بالشهادة | أصلح الثقة بالشهادة، ولا تستخدم `-k` أو insecure bypass. |
| خطأ مسار | النسخة العاملة غير مركبة في الجذر الصحيح | تحقق من `findmnt` ومن أن `ATHAR_EVIDENCE_ROOT=/mnt/athar-private`. |

---

## الخطوة 17: التحقق من النتيجة في ATHAR

عد إلى لوحة ATHAR وافتح **مهام التحليل**. دوّن Job ID وحالته. النجاح الحقيقي في هذه المرحلة هو التسلسل التالي:

```text
created → queued → claimed → running → parsing → completed
```

ثم تحقق من وجود نتيجة محفوظة ومعلومات TShark ونسخة الـAdapter وبصمة النتيجة، ومن تسجيل أحداث Audit وChain of Custody. إذا أصبحت الحالة `failed` أو `timed_out`، احتفظ بالـJob ID وسجل الخطأ؛ لا تحاول إعادة كتابة أوامر TShark يدويًا داخل Job.

---

## الخطوة 18: ما الذي ترسله لي بعد التنفيذ؟

أرسل المعلومات التالية فقط كي أساعدك في التشخيص، مع إخفاء أي سر أو عنوان حساس إن لزم:

| أرسل | لا ترسل |
|---|---|
| هل نجح `redis-cli ping`؟ | كلمة مرور Redis أو MySQL. |
| أول سطر من `/usr/bin/tshark --version`. | `ATHAR_AGENT_TOKEN` أو ملف البيئة. |
| هل نجح `curl -I https://...` دون تحذير شهادة؟ | ملف PCAP أو دليل حقيقي. |
| ناتج `findmnt -no TARGET,OPTIONS /mnt/athar-private`. | مسار أو محتوى دليل حساس. |
| Job ID وحالته، ورسالة خطأ منقحة إن وجدت. | لقطات تعرض رموزًا أو أسرارًا. |

بعد تحقق كل الأدلة السابقة، نراجع معًا هل تسمح النتائج بترقية حالة TShark تدريجيًا. لا نغيّرها مباشرة إلى `end_to_end_verified` قبل اكتمال دورة HTTPS الفعلية ونجاح المراجعة البشرية.
