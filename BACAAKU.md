# BACAAKU.md (README FOR GEN Z)

## What is this, why do another API?

Sebab boleh la dek.

---

## Original idea and the actual reason

Have you ever see **Sabah Public Holiday Fetcher**, yea that is one of my repo I do during internship, because I did not find any API (too lazy too search actually)

Simple simple jak masa tu:

- scrape website
- generate JSON
- deploy static file
- siap, boleh pakai plug and play
- SABAH only (i dunno why)

And then I realise there are some of developer actually encounter the same problem as me, and how do i know? Google Search Console

And when i saw the search trend, and realise my API is Sabah only, i decide to update ~~a year later~~

Tapi masalah dia, kadang benda yg discrap tu ndk tepat bah, sbb pndai2 dorang buat in leau pnya holiday (case where public holiday fall on Sunday, actually its fine if your rest day is Sunday), so its kinda unreliable

Cuti public holiday macam simple, tapi time mau develop system, dia boleh affect:

- calendar
- schedule
- reminder
- booking
- payroll
- attendance
- leave
- overtime
- apa-apa lah business logic yang kira working day

So kalau data pun salah, gege bro/sis🙃 (speaking from personal experience)

---

## Masalah sebenar

Masalah dia bukan “tiada data”

Data ada

Masalah dia

- data ada, tapi scattered
- official source ada, tapi PDF
- website ada, tapi ndk semua blh scrape (kcuali kau hacker lvl 999, sw mention)
- third-party ada, but cannot 100% trust

---

## Kenapa ndk pakai existing API jak?

~~Sbb blh dek~~

~~Sbb ndk bengam dgn taste aku~~

Saja mau test coding skill, and mau cuba try laravel 13 ni mantap ka ndk haha

---

## Kenapa ada admin dashboard?

Sebab holiday data bukan benda yang patut auto-publish kalau source ndk perfect

And PDF table kerajaan

Klau table dia cantik untuk manusia baca ok la, tapi kalau komputer yg baca

```text
b̶̩͇̤̣̓̀̂͋̑͛͛͒̇r̸̺̊̎̾ų̶̧͎͕͑͂̒͂̅̊̌͘h̶̰͇͋̓̑́͗̀ ̸̢̙̙̈́̾͌͗͝w̵̧̹͉͎̾̐̓̓̂̓̀ḩ̸̞̎ỳ̸̨̖͕͇̯̭̍̑̑͑̇̈́͝ ̸̯̈́̇͒̎̆͂̿͝ģ̷̠̝̫̫̻͊̈́̑̏̊͋͘͘͘į̸͎̭͙͇͕̿̆͋̂̌͆̀̏͠v̵̠̭̩̲͇̯̩͑̑̋̂̔͒̕e̵̫̱͙̦͂̔͑̅̔͋̾͘͜ ̷̡̺̗͓̲̞͌̊̆̒̊͑ͅm̸̦̥̥̘̫̖͖̈́̿̈ẽ̶̥̬ ̴͎͙̙͚̞̔́͂͒̓̈͘̚͝t̸͈͉̟̪͖̭͑̏͊͒́͋͑̈h̸͖̀͛͂̾i̶̯͓̩̣̤̺̝̅̌͛͂̉̆́͜s̶͈̺̣̥̾̐̿͊̀̌͋͘
```

Lain la kalau kau suruh chatgpt yg baca, tapi still mau manusia pnya review baru tepat bro/sis

**Update: LLM ndk dpt baca government pdf, tested with gemini-3.1-flash-lite-preview(bcoz im broke), too many hallucinations**

So dashboard tu untuk:

- upload sauce
- import CSV/PDF
- preview draft holiday
- fix wrong row
- approve
- publish
- add override
- keep audit trail

Basically, machine tu extract2 jak, tpi manusia juga confirm data

## Project mood

Flexing my ~~mythic~~ warrior coding skill

Baca README.md kalau mau professional document (boring version)

But if you read until here, 👑

Thanks for coming to my Ted talk
