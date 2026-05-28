# -*- coding: utf-8 -*-
import json, random

random.seed(20260528)

HEX = "0123456789abcdef"
def uuid4():
    def blk(n): return "".join(random.choice(HEX) for _ in range(n))
    # version 4, variant 8-b
    return f"{blk(8)}-{blk(4)}-4{blk(3)}-{random.choice('89ab')}{blk(3)}-{blk(12)}"

PROC = {
    "Obóz przygodowy":  "f1a2b3c4-0000-0000-0000-000000000001",
    "Obóz wypoczynkowy":"f1a2b3c4-0000-0000-0000-000000000002",
    "Obóz sportowy":    "f1a2b3c4-0000-0000-0000-000000000003",
    "Kolonia letnia":   "f1a2b3c4-0000-0000-0000-000000000004",
    "Obóz aktywny":     "f1a2b3c4-0000-0000-0000-000000000005",
    "Obóz naukowy":     "f1a2b3c4-0000-0000-0000-000000000006",
    "Obóz językowy":    "f1a2b3c4-0000-0000-0000-000000000007",
    "Obóz wodny":       "f1a2b3c4-0000-0000-0000-000000000008",
    "Obóz rowerowy":    "f1a2b3c4-0000-0000-0000-000000000009",
    "Obóz górski":      "f1a2b3c4-0000-0000-0000-000000000010",
    "Półkolonie":       "f1a2b3c4-0000-0000-0000-000000000011",
    "Obóz rodzinny":    "f1a2b3c4-0000-0000-0000-000000000012",
    "Kolonia zimowa":   "f1a2b3c4-0000-0000-0000-000000000013",
    "Obóz zimowy":      "f1a2b3c4-0000-0000-0000-000000000014",
}

GPS = {
    "Kraków":   (50.0681, 19.9452, "ul. Bosacka 18, Kraków"),
    "Katowice": (50.2589, 19.0235, "ul. Dworcowa 3, Katowice"),
    "Wrocław":  (51.1079, 17.0385, "ul. Sucha 1, Wrocław"),
    "Warszawa": (52.2297, 21.0122, "Al. Jerozolimskie 142, Warszawa"),
}

# --- date slots ---
S9 = {
    "A": ("2026-06-22","2026-06-30"), "B": ("2026-07-01","2026-07-09"),
    "C": ("2026-07-10","2026-07-18"), "D": ("2026-07-19","2026-07-27"),
    "E": ("2026-07-28","2026-08-05"), "F": ("2026-08-06","2026-08-14"),
    "G": ("2026-08-15","2026-08-23"),
}
S11 = {
    "A11": ("2026-06-21","2026-07-01"), "B11": ("2026-07-05","2026-07-15"),
    "C11": ("2026-07-19","2026-07-29"), "D11": ("2026-08-02","2026-08-12"),
    "E11": ("2026-08-16","2026-08-26"),
}
S7 = {
    "a7": ("2026-06-22","2026-06-28"), "b7": ("2026-06-29","2026-07-05"),
    "c7": ("2026-07-06","2026-07-12"), "d7": ("2026-07-13","2026-07-19"),
    "e7": ("2026-07-20","2026-07-26"), "f7": ("2026-07-27","2026-08-02"),
    "g7": ("2026-08-03","2026-08-09"), "h7": ("2026-08-10","2026-08-16"),
}
S5 = {
    "W1": ("2026-06-22","2026-06-26"), "W2": ("2026-06-29","2026-07-03"),
    "W3": ("2026-07-06","2026-07-10"), "W4": ("2026-07-13","2026-07-17"),
    "W5": ("2026-07-20","2026-07-24"), "W6": ("2026-07-27","2026-07-31"),
    "W7": ("2026-08-03","2026-08-07"), "W8": ("2026-08-10","2026-08-14"),
    "W9": ("2026-08-17","2026-08-21"), "W10":("2026-08-24","2026-08-28"),
}
# 8-day summer (for 8-day events) derived from 9-day starts but 8 days span
S8 = {
    "A": ("2026-06-22","2026-06-29"), "B": ("2026-07-01","2026-07-08"),
    "C": ("2026-07-10","2026-07-17"), "D": ("2026-07-19","2026-07-26"),
    "E": ("2026-07-28","2026-08-04"), "F": ("2026-08-06","2026-08-13"),
    "G": ("2026-08-15","2026-08-22"),
}
ROMAN = ["I","II","III","IV","V","VI"]

# seat presets to cover all buckets: (available, all)
# available: >30% ; few_left: <=30% ; almost_full: <=10% ; full: 0
SEAT_PRESETS = [
    (14, 20),  # 70% available
    (10, 18),  # 55% available
    (5,  18),  # 27% few_left
    (4,  20),  # 20% few_left
    (2,  22),  # 9%  almost_full
    (1,  18),  # 5%  almost_full
    (0,  16),  # full
    (12, 24),  # 50% available
    (3,  16),  # 18% few_left
    (0,  20),  # full
]

def seat(i):
    return SEAT_PRESETS[i % len(SEAT_PRESETS)]

DOCS = [
    {"name": "Regulamin uczestnika", "url": "https://camps4you.pl/docs/regulamin.pdf"},
    {"name": "Karta zdrowia",        "url": "https://camps4you.pl/docs/karta-zdrowia.pdf"},
    {"name": "Karta kwalifikacyjna uczestnika", "url": "https://camps4you.pl/docs/karta-kwalifikacyjna.pdf"},
]

CONTACTS = [
    {"firstname":"Anna","lastname":"Kowalska","email":"anna@camps4you.pl","phone":"+48 12 654 25 60"},
    {"firstname":"Marek","lastname":"Nowak","email":"marek@camps4you.pl","phone":"+48 12 654 25 61"},
    {"firstname":"Katarzyna","lastname":"Wiśniewska","email":"kasia@camps4you.pl","phone":"+48 12 654 25 62"},
    {"firstname":"Piotr","lastname":"Zieliński","email":"piotr@camps4you.pl","phone":"+48 12 654 25 63"},
]

def general_terms(diet="standardowa"):
    return {
        "insurance": "Każdy uczestnik objęty jest ubezpieczeniem NNW (następstwa nieszczęśliwych wypadków) na sumę 20 000 zł przez cały czas trwania turnusu, łącznie z transportem.",
        "drugOrdering": "Leki przyjmowane na stałe należy przekazać kierownikowi wypoczynku w oryginalnych opakowaniach wraz z pisemną instrukcją dawkowania podpisaną przez rodzica lub opiekuna.",
        "specialDiet": f"Zapewniamy dietę {diet}. Diety specjalne (bezglutenowa, bezmleczna, wegetariańska) realizujemy po wcześniejszym zgłoszeniu na minimum 14 dni przed rozpoczęciem turnusu.",
        "deadlinesAndDocumentsInfo": "Komplet dokumentów (karta kwalifikacyjna, zgody i oświadczenia) prosimy dostarczyć najpóźniej na 7 dni przed wyjazdem. Zaliczka 30% płatna w ciągu 3 dni od rezerwacji, pozostała kwota na 21 dni przed turnusem.",
    }

def instructions(extra_take=""):
    base = ("<li>dowód tożsamości lub legitymacja szkolna</li>"
            "<li>strój sportowy i obuwie sportowe (2 pary)</li>"
            "<li>klapki, strój kąpielowy i ręcznik</li>"
            "<li>kurtka przeciwdeszczowa i ciepła bluza</li>"
            "<li>nakrycie głowy i krem z filtrem UV</li>"
            "<li>przybory toaletowe i ręczniki</li>"
            "<li>mała kieszonkowa na drobne wydatki</li>")
    return {
        "howToPrepare": "<p>Przed wyjazdem prosimy o spakowanie bagażu zgodnie z poniższą listą oraz dostarczenie kompletu dokumentów. Zalecamy oznaczenie rzeczy imieniem i nazwiskiem dziecka. W dniu wyjazdu prosimy o przybycie na miejsce zbiórki 15 minut przed planowanym odjazdem.</p>",
        "whatToTake": "<ul>" + base + extra_take + "</ul>",
    }

def media(lead, *vids):
    multi = [lead, lead.replace("-f-small-60-40-75-1.webp", "-f-small-60-40-75-2.webp")]
    vlist = list(vids) if vids else ["https://www.youtube.com/watch?v=" + "".join(random.choice(HEX) for _ in range(11))]
    return multi, vlist[0], vlist

def yt():
    return "https://www.youtube.com/watch?v=" + "".join(random.choice("abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789_-") for _ in range(11))

def mk_meeting_start(city, date, hour):
    lat, lng, addr = GPS[city]
    return {
        "name": f"{city} Główny" if city in ("Kraków","Wrocław") else city,
        "address": addr,
        "gps": {"lat": lat, "lng": lng},
        "description": f"Zbiórka 15 minut przed odjazdem przy autokarze. {city} — odjazd punktualnie.",
        "date": date, "hour": hour,
    }

def mk_meeting_return(city, date, hour):
    lat, lng, addr = GPS[city]
    return {
        "address": addr,
        "gps": {"lat": lat, "lng": lng},
        "description": f"Powrót pod dworzec {city}. Prosimy o odbiór uczestnika o wyznaczonej godzinie.",
        "date": date, "hour": hour,
    }

def venue_meeting_start(loc, date, hour="16:00"):
    return {
        "name": loc["name"],
        "address": f"{loc['address']['address']}, {loc['address']['city']}",
        "gps": {"lat": loc["gps"]["lat"], "lng": loc["gps"]["lng"]},
        "description": "Przyjazd własny do ośrodka. Zakwaterowanie od godziny podanej obok.",
        "date": date, "hour": hour,
    }

def session_custom(price_note=None, room="Pokoje 4-osobowe z łazienką"):
    cf = [
        {"key":"znizka_wczesna","label":"Zniżka za wczesną rezerwację","value":"5% przy rezerwacji do 1 marca 2026"},
        {"key":"typ_zakwaterowania","label":"Typ zakwaterowania","value":room},
    ]
    if price_note:
        cf.append({"key":"doplata","label":"Dopłata","value":price_note})
    return cf

def season_from_date(dfrom):
    m = int(dfrom[5:7])
    if m in (12, 1, 2):
        return "zima"
    if m in (3, 4, 5):
        return "wiosna"
    if m in (6, 7, 8):
        return "lato"
    return "jesień"

def make_session(idx, name, dfrom, dto, days, price, transport_type, loc,
                 bus_cities=None, ret_city="Kraków", price_note=None, room="Pokoje 4-osobowe z łazienką",
                 return_offset_hour="19:30"):
    sid = uuid4()
    av, alls = seat(idx)
    s = {
        "id": sid,
        "name": name,
        "dateFrom": dfrom,
        "dateTo": dto,
        "numberOfDays": days,
        "priceFrom": price,
        "season": season_from_date(dfrom),
    }
    if transport_type == "bus":
        cities = bus_cities or ["Kraków"]
        s["transport"] = {
            "type": "bus",
            "description": "Autokar klimatyzowany z " + " / ".join(cities) + " (dojazd w cenie).",
        }
        starts = []
        hour = "07:00"
        for ci in cities:
            starts.append(mk_meeting_start(ci, dfrom, hour))
            hour = "08:30"
        s["meetingPoints_start"] = starts
        s["meetingPoints_return"] = [mk_meeting_return(ret_city, dto, return_offset_hour)]
    else:  # own
        s["transport"] = {
            "type": "own",
            "description": "Dojazd własny do ośrodka. Parking dostępny na miejscu.",
        }
        s["meetingPoints_start"] = [venue_meeting_start(loc, dfrom)]
        s["meetingPoints_return"] = []
    s["seatsAvailable"] = av
    s["seatsAll"] = alls
    s["reservationUrl"] = f"https://campsflow.pl/embed/camps4you/register?session={sid}"
    s["customFields"] = session_custom(price_note, room)
    return s

# global counter to vary seat presets across all sessions
_ctr = [0]
def nxt():
    v = _ctr[0]; _ctr[0]+=1; return v

def build_event(cfg):
    eid = uuid4()
    proc_name = cfg["proc"]
    loc = cfg["loc"]
    multi, lead_vid, vids = media(cfg["lead"], cfg.get("video"))
    ev = {
        "id": eid,
        "eventClass": cfg["class"],
        "eventProcess": {"id": PROC[proc_name], "name": proc_name},
        "name": cfg["name"],
        "minAge": cfg["minAge"],
        "maxAge": cfg["maxAge"],
        "eventTags": cfg.get("tags", []),
        "eventProfile": cfg["profile"],
        "localization": loc,
        "multimediaUrls": multi,
        "leadImageUrl": cfg["lead"],
        "leadVideoUrl": lead_vid,
        "videoUrls": vids,
        "description": cfg["desc"],
        "documents": DOCS,
        "generalTerms": general_terms(cfg.get("diet","standardowa")),
        "instructions": instructions(cfg.get("extra_take","")),
        "reservationUrl": f"https://campsflow.pl/embed/camps4you/register?event={eid}",
        "contact": cfg["contact"],
        "customFields": cfg["custom"],
        "turnusy": [],
    }
    for i, sc in enumerate(cfg["sessions"]):
        ev["turnusy"].append(make_session(
            nxt(),
            f"Turnus {ROMAN[i]}",
            sc["from"], sc["to"], sc["days"], sc["price"],
            sc["transport"], loc,
            bus_cities=sc.get("bus_cities"),
            ret_city=sc.get("ret_city","Kraków"),
            price_note=sc.get("price_note"),
            room=sc.get("room","Pokoje 4-osobowe z łazienką"),
        ))
    return ev

def loc(dest, name, addr, city, code, lat, lng, web, email, phone, descr):
    return {
        "destination": dest,
        "name": name,
        "address": {"address": addr, "city": city, "code": code, "country": "PL"},
        "gps": {"lat": lat, "lng": lng},
        "webpage": web, "email": email, "phone": phone,
        "description": descr, "imageUrls": [],
    }

def sess9(slots, price, transport_map):
    """slots: list of slot keys from S9; transport_map: list of 'bus'/'own' parallel."""
    out=[]
    for sl, tr in zip(slots, transport_map):
        f,t = S9[sl]
        out.append({"from":f,"to":t,"days":9,"price":price,"transport":tr})
    return out

def sess_custom(slots_src, days, price, transport_map, bus_cities_map=None, price_note=None):
    out=[]
    for i,(sl,tr) in enumerate(zip(slots_src, transport_map)):
        f,t = sl
        item = {"from":f,"to":t,"days":days,"price":price,"transport":tr}
        if bus_cities_map and i < len(bus_cities_map) and bus_cities_map[i]:
            item["bus_cities"] = bus_cities_map[i]
        if price_note:
            item["price_note"] = price_note
        out.append(item)
    return out

# ---------------- Localizations ----------------
LOC_MYCZKOWCE = loc("Bieszczady","Ośrodek Sportów Wodnych Myczkowce","ul. Jeziorna 3","Myczkowce","38-543",
    49.3849, 22.1633, "https://myczkowce.pl","kontakt@myczkowce.pl","+48 13 469 10 00",
    "Ośrodek położony nad zalewem Myczkowieckim w sercu Bieszczadów, z bezpośrednim dostępem do wody, przystanią kajakową i bazą sportów wodnych. Otoczony lasami, idealny do aktywnego wypoczynku.")
LOC_DZWIRZYNO = loc("Bałtyk","Ośrodek Wypoczynkowy Bryza","ul. Wyzwolenia 24","Dźwirzyno","78-131",
    54.1547, 15.4072, "https://bryza-dzwirzyno.pl","recepcja@bryza-dzwirzyno.pl","+48 94 358 51 00",
    "Nowoczesny ośrodek 300 metrów od szerokiej, piaszczystej plaży w Dźwirzynie. Własne boiska, basen i szkoła sportów wodnych nad samym morzem.")
LOC_OCHOTNICA = loc("Pieniny","Ośrodek Górski Gorce","Ochotnica Górna 215","Ochotnica Górna","34-453",
    49.5306, 20.3361, "https://gorce-ochotnica.pl","biuro@gorce-ochotnica.pl","+48 18 262 40 00",
    "Komfortowy ośrodek w dolinie Ochotnicy, u podnóża Gorców i Pienin. Dostęp do spływów pontonowych na Dunajcu i licznych szlaków górskich dla dzieci.")
LOC_ZUBRZYCA = loc("Małopolska","Centrum Sportowe Orawa","Zubrzyca Górna 188","Zubrzyca Górna","34-484",
    49.5489, 19.6517, "https://orawa-sport.pl","kontakt@orawa-sport.pl","+48 18 285 30 00",
    "Centrum sportowe na Orawie z halą gimnastyczną, parkiem parkour, ścianką wspinaczkową i ścieżkami rowerowymi. Baza idealna dla obozów sportowych.")
LOC_STASINDA = loc("Tatry","Centrum GRAND STASINDA","ul. Wojciechówka 25","Bukowina Tatrzańska","34-530",
    49.3361, 20.1067, "https://grandstasinda.pl","recepcja@grandstasinda.pl","+48 18 200 00 00",
    "Nowoczesne centrum w Bukowinie Tatrzańskiej z basenami termalnymi, salami warsztatowymi i pełnym zapleczem sportowym. Widok na panoramę Tatr.")
LOC_SKALNITY = loc("Tatry","Hotel SKALNITY","ul. Kościuszki 14","Bukowina Tatrzańska","34-530",
    49.3328, 20.1019, "https://skalnity.pl","recepcja@skalnity.pl","+48 18 200 01 00",
    "Hotel SKALNITY w Bukowinie Tatrzańskiej u stóp wyciągów narciarskich, z basenem i strefą wellness. Doskonała baza zimowych obozów narciarskich.")
LOC_MIKOLAJKI = loc("Mazury","Mazurskie Centrum Żeglarskie","ul. Kowalska 3","Mikołajki","11-730",
    53.8019, 21.5772, "https://mcz-mikolajki.pl","biuro@mcz-mikolajki.pl","+48 87 421 50 00",
    "Centrum żeglarskie nad jeziorem Mikołajskim z własną mariną, flotą jachtów i sprzętem do sportów wodnych. Serce Krainy Wielkich Jezior Mazurskich.")
LOC_ZAKOPANE = loc("Tatry","Schronisko Pod Reglami","ul. Kuźnice 12","Zakopane","34-500",
    49.2667, 19.9889, "https://podreglami.pl","kontakt@podreglami.pl","+48 18 201 20 00",
    "Baza górska w Zakopanem u wejścia w Tatry, z bezpośrednim dostępem do szlaków, ścianek wspinaczkowych i via ferraty. Profesjonalna kadra instruktorów.")
LOC_KRASNOBROD = loc("Roztocze","Ośrodek Sportowy Roztocze","ul. Sanatoryjna 8","Krasnobród","22-440",
    50.5419, 23.2114, "https://roztocze-sport.pl","biuro@roztocze-sport.pl","+48 84 660 70 00",
    "Ośrodek sportowy w uzdrowiskowym Krasnobrodzie na Roztoczu, z pełnowymiarowymi boiskami, halą i zalewem. Czyste powietrze i kompleks sportowy w jednym miejscu.")
LOC_RUSIECKI = loc("Kraków","Wake Park Kraków — Przylasek Rusiecki","ul. Rusiecka 100","Kraków","31-997",
    50.0589, 20.1019, "https://wakepark-krakow.pl","kontakt@wakepark-krakow.pl","+48 12 300 40 00",
    "Wyciąg nart wodnych nad zalewem Przylasek Rusiecki w granicach Krakowa. Pełen sprzęt wakeboardowy, instruktorzy i strefa plażowa — idealne na półkolonie wodne.")
LOC_KORONA = loc("Kraków","KS Korona — Kompleks Sportowy","ul. Kalwaryjska 9-15","Kraków","30-504",
    50.0383, 19.9444, "https://korona-krakow.pl","polkolonie@korona-krakow.pl","+48 12 656 10 00",
    "Kompleks sportowy KS Korona w centrum Krakowa z basenem, halą, boiskami i salami warsztatowymi. Doskonała baza dla miejskich półkolonii.")

# ---------------- Build events ----------------
events = []

# 1 Fabryka Adrenaliny — Bieszczady
events.append(build_event({
    "class":"YOUTH_CAMP","proc":"Obóz przygodowy","name":"Fabryka Adrenaliny — Bieszczady 2026",
    "minAge":10,"maxAge":16,"tags":["hit sezonu"],"profile":["active","przygoda","góry"],
    "loc":LOC_MYCZKOWCE,"lead":"https://camps4you.pl/tempOpen/images/offer-list-39-f-small-60-40-75-1.webp",
    "video":yt(),
    "desc":{
        "general":"<p>Fabryka Adrenaliny to nasz sztandarowy obóz przygodowy w sercu Bieszczadów, nad malowniczym zalewem Myczkowieckim. To propozycja dla młodzieży, która nie boi się wyzwań i chce spędzić wakacje aktywnie — z dala od telefonu, a blisko natury.</p><p>Każdy dzień to porcja nowych emocji: poranne spływy kajakowe po tafli zalewu, popołudniowa wspinaczka na ściance i sztucznych formacjach skalnych oraz wieczorne zajęcia survivalowe pod okiem doświadczonych instruktorów. Uczymy budowy szałasu, rozpalania ogniska i czytania mapy terenu.</p><p>Nad bezpieczeństwem czuwa wykwalifikowana kadra ratowników i instruktorów. Grupy są kameralne, dzięki czemu każdy uczestnik otrzymuje indywidualne wsparcie i wraca do domu z nowymi umiejętnościami oraz garścią niezapomnianych wspomnień.</p>",
        "program":"<ul><li>Spływy kajakowe po zalewie Myczkowieckim z instruktorem</li><li>Wspinaczka na ściance i kurs asekuracji</li><li>Zajęcia survivalowe: budowa schronienia, ognisko, orientacja w terenie</li><li>Tor przeszkód i gry terenowe</li><li>Wieczorne ogniska z muzyką i podchody</li><li>Wycieczka piesza na bieszczadzkie połoniny</li></ul>",
        "priceInclude":"<ul><li>Zakwaterowanie w ośrodku nad wodą (9 dni / 8 nocy)</li><li>Pełne wyżywienie — 4 posiłki dziennie</li><li>Opieka wykwalifikowanej kadry i ratowników wodnych</li><li>Sprzęt: kajaki, kamizelki, sprzęt wspinaczkowy</li><li>Ubezpieczenie NNW</li><li>Transport autokarem (przy wybranych turnusach)</li></ul>",
    },
    "contact":CONTACTS[0],
    "custom":[
        {"key":"poziom_trudnosci","label":"Poziom trudności","value":"Średniozaawansowany"},
        {"key":"liczebnosc_grupy","label":"Liczebność grupy","value":"Maksymalnie 18 osób"},
    ],
    "sessions": sess9(["A","B","C","D","E"], 309000, ["bus","bus","own","bus","own"]),
}))

# 2 Chill Sea Camp — Dźwirzyno (11-day)
events.append(build_event({
    "class":"YOUTH_CAMP","proc":"Obóz wypoczynkowy","name":"Chill Sea Camp — Dźwirzyno 2026",
    "minAge":13,"maxAge":17,"tags":["nowość"],"profile":["chill","sport","active"],
    "loc":LOC_DZWIRZYNO,"lead":"https://camps4you.pl/tempOpen/images/offer-list-93-f-small-60-40-75-1.webp",
    "desc":{
        "general":"<p>Chill Sea Camp to wakacje nad Bałtykiem w wyluzowanej atmosferze, stworzone dla nastolatków, którzy chcą połączyć relaks na plaży z aktywnością. Ośrodek leży zaledwie 300 metrów od szerokiej, piaszczystej plaży w Dźwirzynie.</p><p>Dni wypełnione są beach volleyem, porannym joggingiem brzegiem morza i kąpielami pod okiem ratowników. Wieczory upływają na wspólnym grillu, kinie pod chmurką i spotkaniach integracyjnych. To idealny balans między ruchem a wypoczynkiem.</p><p>Stawiamy na swobodną atmosferę i samodzielność uczestników, przy zachowaniu pełnej opieki wychowawców. Chill Sea Camp to wakacje, z których wraca się z opalenizną, nowymi znajomościami i naładowanymi bateriami.</p>",
        "program":"<ul><li>Codzienne kąpiele morskie pod opieką ratowników</li><li>Beach volley i piłka plażowa — turnieje</li><li>Poranny jogging i rozciąganie nad brzegiem morza</li><li>Warsztaty fotografii mobilnej i tworzenia reelsów</li><li>Wieczorne grille, ognisko i kino plenerowe</li><li>Wycieczka do Kołobrzegu i rejs statkiem</li></ul>",
        "priceInclude":"<ul><li>Zakwaterowanie 300 m od plaży (11 dni / 10 nocy)</li><li>Pełne wyżywienie — 4 posiłki dziennie</li><li>Opieka wychowawców i ratowników</li><li>Sprzęt sportowy i plażowy</li><li>Ubezpieczenie NNW</li><li>Wstęp na atrakcje wg programu</li></ul>",
    },
    "contact":CONTACTS[1],
    "custom":[
        {"key":"poziom_trudnosci","label":"Poziom trudności","value":"Dla każdego"},
        {"key":"odleglosc_plaza","label":"Odległość do plaży","value":"300 m"},
    ],
    "sessions": sess_custom([S11["A11"],S11["B11"],S11["C11"],S11["D11"],S11["E11"]], 11, 349000,
        ["bus","own","bus","own","bus"]),
}))

# 3 Surf Camp — Dźwirzyno (11-day)
events.append(build_event({
    "class":"YOUTH_CAMP","proc":"Obóz sportowy","name":"Surf Camp — Dźwirzyno 2026",
    "minAge":12,"maxAge":17,"tags":["hit sezonu"],"profile":["active","surf","sport"],
    "loc":LOC_DZWIRZYNO,"lead":"https://camps4you.pl/tempOpen/images/offer-list-48-f-small-60-40-75-1.webp",
    "video":yt(),
    "desc":{
        "general":"<p>Surf Camp w Dźwirzynie to obóz dla młodzieży marzącej o opanowaniu fal Bałtyku. Pod okiem licencjonowanych instruktorów windsurfingu uczestnicy stawiają pierwsze kroki na desce, a bardziej zaawansowani szlifują technikę halsowania i ślizgu.</p><p>Szkolenie odbywa się w małych grupach, na sprzęcie dobranym do wieku i poziomu. Teoria przeplata się z praktyką na wodzie, a po zajęciach czeka regeneracja na plaży, beach volley i wspólne wieczory. Bezpieczeństwo zapewnia asekuracja motorowa i pełne wyposażenie ratunkowe.</p><p>To więcej niż obóz sportowy — to wakacyjna przygoda, która rozbudza pasję do sportów wodnych i daje realne umiejętności potwierdzone dyplomem ukończenia kursu.</p>",
        "program":"<ul><li>Codzienne szkolenie windsurfingowe z instruktorem (2 bloki)</li><li>Teoria żeglarska i meteorologia dla surferów</li><li>Trening kondycyjny i balansu na desce</li><li>Beach volley i gry zespołowe</li><li>Wieczory tematyczne i ognisko</li><li>Egzamin praktyczny i wręczenie dyplomów</li></ul>",
        "priceInclude":"<ul><li>Zakwaterowanie 300 m od plaży (11 dni / 10 nocy)</li><li>Pełne wyżywienie — 4 posiłki dziennie</li><li>Kurs windsurfingu z licencjonowanym instruktorem</li><li>Sprzęt: deski, żagle, pianki, kamizelki</li><li>Asekuracja motorowa i opieka ratowników</li><li>Ubezpieczenie NNW i dyplom ukończenia kursu</li></ul>",
    },
    "contact":CONTACTS[0],
    "custom":[
        {"key":"poziom_trudnosci","label":"Poziom trudności","value":"Od podstaw do średniozaawansowanego"},
        {"key":"sprzet","label":"Sprzęt","value":"Wliczony w cenę"},
    ],
    "sessions": sess_custom([S11["A11"],S11["B11"],S11["C11"],S11["D11"],S11["E11"]], 11, 359000,
        ["bus","bus","own","bus","own"]),
}))

# 4 Kite Camp — Dźwirzyno (11-day, 4 turnusy)
events.append(build_event({
    "class":"YOUTH_CAMP","proc":"Obóz sportowy","name":"Kite Camp — Dźwirzyno 2026",
    "minAge":13,"maxAge":17,"tags":["nowość"],"profile":["active","kite","sport"],
    "loc":LOC_DZWIRZYNO,"lead":"https://camps4you.pl/tempOpen/images/offer-list-92-f-small-60-40-75-1.webp",
    "desc":{
        "general":"<p>Kite Camp to obóz kitesurfingowy nad Bałtykiem dla młodzieży gotowej poczuć moc wiatru. Naukę zaczynamy od bezpiecznego sterowania latawcem na plaży, by stopniowo przejść do startów na wodzie pod stałym nadzorem instruktorów.</p><p>Kitesurfing to sport wymagający, dlatego pracujemy w bardzo małych grupach i kładziemy ogromny nacisk na bezpieczeństwo — sprzęt asekuracyjny, łódź ratownicza i sprawdzone procedury to standard. Dni na wodzie przeplatamy regeneracją i integracją na plaży.</p><p>Uczestnicy kończą obóz z solidnymi podstawami kitesurfingu, świadomością wiatru i wody oraz nowymi przyjaźniami. To wakacje pełne adrenaliny i prawdziwego sportu.</p>",
        "program":"<ul><li>Szkolenie kitesurfingowe — od latawca treningowego po starty na wodzie</li><li>Teoria: okno wiatrowe, bezpieczeństwo, system depower</li><li>Trening balansu i kondycji</li><li>Sesje na wodzie z asekuracją łodzią</li><li>Wieczory integracyjne i ognisko</li><li>Analiza nagrań i feedback instruktorski</li></ul>",
        "priceInclude":"<ul><li>Zakwaterowanie 300 m od plaży (11 dni / 10 nocy)</li><li>Pełne wyżywienie — 4 posiłki dziennie</li><li>Kurs kitesurfingu w małych grupach</li><li>Pełen sprzęt: latawce, deski, trapezy, pianki</li><li>Asekuracja łodzią i opieka ratowników</li><li>Ubezpieczenie NNW</li></ul>",
    },
    "contact":CONTACTS[1],
    "custom":[
        {"key":"poziom_trudnosci","label":"Poziom trudności","value":"Zaawansowany"},
        {"key":"liczebnosc_grupy","label":"Liczebność grupy szkoleniowej","value":"Maksymalnie 4 osoby na instruktora"},
    ],
    "sessions": sess_custom([S11["B11"],S11["C11"],S11["D11"],S11["E11"]], 11, 369000,
        ["bus","own","bus","own"]),
}))

# 5 Moc Aktywności — Ochotnica (8-day)
events.append(build_event({
    "class":"YOUTH_CAMP","proc":"Obóz aktywny","name":"Moc Aktywności — Ochotnica 2026",
    "minAge":8,"maxAge":14,"tags":["polecamy"],"profile":["active","przygoda","góry"],
    "loc":LOC_OCHOTNICA,"lead":"https://camps4you.pl/tempOpen/images/offer-list-8-f-small-60-40-75-1.webp",
    "desc":{
        "general":"<p>Moc Aktywności to obóz dla dzieci, które kochają ruch i odkrywanie gór. Baza w dolinie Ochotnicy, u podnóża Gorców i Pienin, daje dostęp do spływów pontonowych, szlaków górskich i bezkresu zielonej natury.</p><p>Program łączy spływy Dunajcem, łatwe wędrówki dostosowane do wieku, gry terenowe i codzienną dawkę sportu. Wszystko prowadzone w przyjaznej, wakacyjnej atmosferze, gdzie każde dziecko znajdzie coś dla siebie i nawiąże nowe przyjaźnie.</p><p>Doświadczeni wychowawcy dbają o bezpieczeństwo i dobre samopoczucie najmłodszych. To obóz, który uczy samodzielności, rozbudza miłość do gór i daje dzieciom mnóstwo radości z aktywnego wypoczynku.</p>",
        "program":"<ul><li>Spływ pontonowy doliną Dunajca</li><li>Łatwe wędrówki górskie w Gorcach i Pieninach</li><li>Gry terenowe i podchody</li><li>Codzienne zajęcia sportowe i zabawy ruchowe</li><li>Warsztaty plastyczne i ogniska z pieczeniem kiełbasek</li><li>Wieczorne dyskoteki i konkursy</li></ul>",
        "priceInclude":"<ul><li>Zakwaterowanie w ośrodku górskim (8 dni / 7 nocy)</li><li>Pełne wyżywienie — 4 posiłki dziennie</li><li>Opieka doświadczonych wychowawców</li><li>Sprzęt i bilety wstępu wg programu</li><li>Ubezpieczenie NNW</li><li>Transport autokarem (przy wybranych turnusach)</li></ul>",
    },
    "contact":CONTACTS[2],
    "custom":[
        {"key":"poziom_trudnosci","label":"Poziom trudności","value":"Łatwy — dla dzieci"},
        {"key":"opieka","label":"Opieka","value":"1 wychowawca na 10 dzieci"},
    ],
    "sessions": sess_custom([S8["A"],S8["B"],S8["C"],S8["D"],S8["E"]], 8, 289000,
        ["bus","bus","own","bus","own"]),
}))

# 6 Happy Kids — Ochotnica (7-day, REGULAR_CAMP, 6 turnusy)
events.append(build_event({
    "class":"REGULAR_CAMP","proc":"Kolonia letnia","name":"Happy Kids — Ochotnica 2026",
    "minAge":6,"maxAge":10,"tags":["polecamy"],"profile":["chill","active"],
    "loc":LOC_OCHOTNICA,"lead":"https://camps4you.pl/tempOpen/images/offer-list-51-f-small-60-40-75-1.webp",
    "desc":{
        "general":"<p>Happy Kids to pierwsza kolonia dla najmłodszych — łagodne wprowadzenie w świat wakacji bez rodziców. W spokojnej dolinie Ochotnicy dzieci bawią się, poznają rówieśników i zdobywają samodzielność w bezpiecznym, troskliwym otoczeniu.</p><p>Program dostosowany jest do możliwości 6–10-latków: krótkie spacery, zabawy ruchowe na świeżym powietrzu, warsztaty plastyczne i baśniowe wieczory. Tempo jest spokojne, z czasem na odpoczynek i drzemkę dla młodszych dzieci.</p><p>Każdą grupą opiekuje się troskliwa kadra w komfortowej proporcji opiekun–dziecko. Happy Kids to ciepły start wakacyjnych przygód, z którego dzieci wracają uśmiechnięte i dumne ze swojej samodzielności.</p>",
        "program":"<ul><li>Zabawy ruchowe i gry na świeżym powietrzu</li><li>Krótkie spacery przyrodnicze dostosowane do najmłodszych</li><li>Warsztaty plastyczne i rękodzieło</li><li>Bajkowe wieczory i teatrzyki</li><li>Mini-olimpiada i konkursy z nagrodami</li><li>Ogniska z pieczeniem kiełbasek</li></ul>",
        "priceInclude":"<ul><li>Zakwaterowanie w ośrodku górskim (7 dni / 6 nocy)</li><li>Pełne wyżywienie — 4 posiłki dziennie i podwieczorek</li><li>Wzmożona opieka troskliwej kadry</li><li>Materiały plastyczne i nagrody</li><li>Ubezpieczenie NNW</li><li>Transport autokarem (przy wybranych turnusach)</li></ul>",
    },
    "contact":CONTACTS[2],
    "diet":"lekkostrawna dla dzieci",
    "custom":[
        {"key":"poziom_trudnosci","label":"Poziom trudności","value":"Najłatwiejszy — pierwsza kolonia"},
        {"key":"opieka","label":"Opieka","value":"1 wychowawca na 8 dzieci"},
    ],
    "sessions": sess_custom([S7["a7"],S7["b7"],S7["c7"],S7["d7"],S7["e7"],S7["f7"]], 7, 189000,
        ["bus","bus","own","bus","own","bus"]),
}))

# 7 Gym & Parkour Camp — Zubrzyca (9-day)
events.append(build_event({
    "class":"YOUTH_CAMP","proc":"Obóz sportowy","name":"Gym & Parkour Camp — Zubrzyca 2026",
    "minAge":10,"maxAge":16,"profile":["active","sport","przygoda"],
    "loc":LOC_ZUBRZYCA,"lead":"https://camps4you.pl/tempOpen/images/offer-list-49-f-small-60-40-75-1.webp",
    "video":yt(),
    "desc":{
        "general":"<p>Gym & Parkour Camp to obóz dla młodzieży, która chce opanować sztukę płynnego pokonywania przeszkód i ruchu akrobatycznego. Baza na Orawie dysponuje halą gimnastyczną, profesjonalnym parkiem parkour i ścieżkami rowerowymi.</p><p>Pod okiem trenerów gimnastyki i parkouru uczestnicy uczą się podstaw bezpiecznego upadania, przewrotów, skoków precyzyjnych i pokonywania przeszkód miejskich. Trening uzupełniają wycieczki rowerowe po orawskich trasach i zajęcia ogólnorozwojowe.</p><p>Bezpieczeństwo to priorytet — pracujemy na matach, w hali i pod stałą asekuracją. Obóz rozwija sprawność, koordynację i pewność siebie, a uczestnicy wracają z nowymi umiejętnościami i mnóstwem energii.</p>",
        "program":"<ul><li>Trening parkour: bezpieczne upadanie, skoki, vault</li><li>Gimnastyka: przewroty, akrobatyka, elementy na macie</li><li>Wycieczki rowerowe po trasach Orawy</li><li>Zajęcia ogólnorozwojowe i kondycyjne</li><li>Tor przeszkód i mini-zawody</li><li>Wieczory integracyjne i ogniska</li></ul>",
        "priceInclude":"<ul><li>Zakwaterowanie w centrum sportowym (9 dni / 8 nocy)</li><li>Pełne wyżywienie — 4 posiłki dziennie</li><li>Trenerzy parkour i gimnastyki</li><li>Dostęp do hali, parku parkour i sprzętu</li><li>Wypożyczenie rowerów i kasków</li><li>Ubezpieczenie NNW</li></ul>",
    },
    "contact":CONTACTS[3],
    "custom":[
        {"key":"poziom_trudnosci","label":"Poziom trudności","value":"Średniozaawansowany"},
        {"key":"trener","label":"Kadra","value":"Certyfikowani trenerzy parkour i gimnastyki"},
    ],
    "sessions": sess9(["A","B","C","D","E"], 329000, ["bus","own","bus","own","bus"]),
}))

# 8 FutureLab Camp — Bukowina (9-day, naukowy)
events.append(build_event({
    "class":"YOUTH_CAMP","proc":"Obóz naukowy","name":"FutureLab Camp — Bukowina Tatrzańska 2026",
    "minAge":10,"maxAge":15,"tags":["nowość"],"profile":["nauka","active","tech"],
    "loc":LOC_STASINDA,"lead":"https://camps4you.pl/tempOpen/images/offer-list-91-f-small-60-40-75-1.webp",
    "video":yt(),
    "desc":{
        "general":"<p>FutureLab Camp to obóz naukowo-technologiczny dla młodych wynalazców w nowoczesnym centrum GRAND STASINDA w Bukowinie Tatrzańskiej. Łączymy fascynującą naukę z górską przygodą, by pokazać, że technologia jest pasjonująca i dostępna dla każdego.</p><p>W salach warsztatowych uczestnicy budują i programują roboty, projektują modele 3D i drukują je na drukarkach 3D oraz przeprowadzają widowiskowe eksperymenty naukowe. Każdy blok prowadzą instruktorzy z doświadczeniem dydaktycznym, którzy tłumaczą trudne zagadnienia w przystępny sposób.</p><p>Naukę równoważymy ruchem na świeżym górskim powietrzu — basenami termalnymi, wycieczkami i sportem. FutureLab Camp rozwija logiczne myślenie, kreatywność i zamiłowanie do nauki, a uczestnicy wracają z własnoręcznie zbudowanym robotem i głową pełną pomysłów.</p>",
        "program":"<ul><li>Budowa i programowanie robotów (LEGO Mindstorms / mBot)</li><li>Modelowanie i druk 3D — od projektu do wydruku</li><li>Eksperymenty naukowe: chemia, fizyka, elektronika</li><li>Podstawy programowania w Scratch i Python</li><li>Wycieczki górskie i baseny termalne</li><li>Prezentacja projektów na zakończenie turnusu</li></ul>",
        "priceInclude":"<ul><li>Zakwaterowanie w centrum GRAND STASINDA (9 dni / 8 nocy)</li><li>Pełne wyżywienie — 4 posiłki dziennie</li><li>Wszystkie warsztaty i materiały (robotyka, druk 3D)</li><li>Instruktorzy techniczni i wychowawcy</li><li>Wstęp na baseny termalne</li><li>Ubezpieczenie NNW</li></ul>",
    },
    "contact":CONTACTS[3],
    "custom":[
        {"key":"poziom_trudnosci","label":"Poziom trudności","value":"Dla początkujących i średnio zaawansowanych"},
        {"key":"sprzet_warsztatowy","label":"Sprzęt warsztatowy","value":"Roboty, drukarki 3D, zestawy elektroniczne w cenie"},
    ],
    "sessions": sess9(["A","B","C","D","E"], 319000, ["bus","bus","own","bus","own"]),
}))

# 9 Chillout Camp — Bukowina (9-day)
events.append(build_event({
    "class":"YOUTH_CAMP","proc":"Obóz wypoczynkowy","name":"Chillout Camp — Bukowina Tatrzańska 2026",
    "minAge":13,"maxAge":17,"profile":["chill","active"],
    "loc":LOC_STASINDA,"lead":"https://camps4you.pl/tempOpen/images/offer-list-50-f-small-60-40-75-1.webp",
    "desc":{
        "general":"<p>Chillout Camp to wakacje w górskim luzie dla nastolatków, którzy chcą odpocząć od miejskiego zgiełku, a jednocześnie nie nudzić się ani chwili. Centrum GRAND STASINDA z basenami termalnymi to idealna baza relaksu z widokiem na Tatry.</p><p>Program balansuje między relaksem a aktywnością: poranne wyjścia w góry, popołudniowe kąpiele w basenach termalnych, sport, warsztaty i wieczorne spotkania przy ognisku. Uczestnicy sami współdecydują o części programu, co buduje poczucie sprawczości i wspólnoty.</p><p>To obóz o swobodnej, przyjaznej atmosferze, gdzie najważniejsze są dobre relacje, regeneracja i radość z gór. Chillout Camp to wakacje, z których wraca się wypoczętym i z nową paczką znajomych.</p>",
        "program":"<ul><li>Łatwe i średnie wycieczki górskie w Tatry</li><li>Codzienne kąpiele w basenach termalnych</li><li>Sport: siatkówka, koszykówka, ścianka wspinaczkowa</li><li>Warsztaty kreatywne (muzyka, fotografia)</li><li>Wieczorne ogniska i spotkania integracyjne</li><li>Dzień z programem ustalanym przez uczestników</li></ul>",
        "priceInclude":"<ul><li>Zakwaterowanie w centrum GRAND STASINDA (9 dni / 8 nocy)</li><li>Pełne wyżywienie — 4 posiłki dziennie</li><li>Opieka wychowawców</li><li>Wstęp na baseny termalne i obiekty sportowe</li><li>Materiały warsztatowe</li><li>Ubezpieczenie NNW</li></ul>",
    },
    "contact":CONTACTS[0],
    "custom":[
        {"key":"poziom_trudnosci","label":"Poziom trudności","value":"Dla każdego"},
        {"key":"baseny","label":"Baseny termalne","value":"Codzienny wstęp w cenie"},
    ],
    "sessions": sess9(["A","B","C","D","E"], 309000, ["bus","own","bus","own","bus"]),
}))

# 10 English is Easy — Bukowina (9-day, 4 turnusy: June, July x2, August)
events.append(build_event({
    "class":"YOUTH_CAMP","proc":"Obóz językowy","name":"English is Easy — Bukowina Tatrzańska 2026",
    "minAge":10,"maxAge":17,"tags":["polecamy"],"profile":["język","chill","active"],
    "loc":LOC_STASINDA,"lead":"https://camps4you.pl/tempOpen/images/offer-list-90-f-small-60-40-75-1.webp",
    "desc":{
        "general":"<p>English is Easy to obóz językowy, który udowadnia, że nauka angielskiego może być świetną zabawą. W górskim centrum GRAND STASINDA łączymy intensywny kurs z wakacyjną przygodą, dzięki czemu język wchodzi do głowy naturalnie i bez stresu.</p><p>Codziennie odbywa się 7 dwugodzinnych bloków językowych w tygodniu, prowadzonych metodą komunikacyjną w małych grupach dobranych według poziomu. Lektorzy stawiają na mówienie, gry językowe i projekty, a nie na nudne wkuwanie regułek.</p><p>Po zajęciach czeka pełen wachlarz górskich atrakcji: wycieczki, baseny termalne i sport — często również w języku angielskim. Uczestnicy kończą turnus z realnie podniesionym poziomem, większą pewnością siebie i certyfikatem.</p>",
        "program":"<ul><li>7 bloków językowych × 2h tygodniowo w małych grupach</li><li>Lekcje metodą komunikacyjną — nacisk na mówienie</li><li>Gry i projekty językowe, English evenings</li><li>Wycieczki górskie z elementami języka</li><li>Baseny termalne i zajęcia sportowe</li><li>Test poziomujący i certyfikat na zakończenie</li></ul>",
        "priceInclude":"<ul><li>Zakwaterowanie w centrum GRAND STASINDA (9 dni / 8 nocy)</li><li>Pełne wyżywienie — 4 posiłki dziennie</li><li>Kurs angielskiego (7 bloków × 2h) z lektorem</li><li>Materiały dydaktyczne i certyfikat</li><li>Wstęp na baseny termalne i atrakcje</li><li>Ubezpieczenie NNW</li></ul>",
    },
    "contact":CONTACTS[1],
    "custom":[
        {"key":"poziom_trudnosci","label":"Poziom językowy","value":"Grupy A1–B2 (test poziomujący)"},
        {"key":"godziny_jezyka","label":"Wymiar zajęć","value":"7 bloków × 2h tygodniowo"},
    ],
    "sessions": sess9(["A","C","D","F"], 319000, ["bus","own","bus","own"]),
}))

# 11 Water World — Mazury (9-day, Kraków+Katowice+Wrocław meeting points)
events.append(build_event({
    "class":"YOUTH_CAMP","proc":"Obóz wodny","name":"Water World — Mazury 2026",
    "minAge":10,"maxAge":16,"profile":["active","sport","woda"],
    "loc":LOC_MIKOLAJKI,"lead":"https://camps4you.pl/tempOpen/images/offer-list-61-f-small-60-40-75-1.webp",
    "video":yt(),
    "desc":{
        "general":"<p>Water World to obóz żeglarsko-wodny w sercu Krainy Wielkich Jezior Mazurskich, nad jeziorem Mikołajskim. Własna marina i flota jachtów sprawiają, że uczestnicy spędzają na wodzie maksimum czasu, ucząc się prawdziwego żeglowania.</p><p>Program obejmuje szkolenie żeglarskie, spływy kajakowe, naukę pływania na desce SUP oraz podstawy ratownictwa wodnego. Wszystko pod okiem instruktorów żeglarstwa i ratowników, w małych, bezpiecznych grupach. Teoria łączy się z codzienną praktyką na jeziorze.</p><p>Mazury to także wspólne rejsy, biwaki nad wodą i wieczory przy ognisku. Water World rozwija samodzielność, pracę zespołową i miłość do wody, a uczestnicy zdobywają solidne podstawy żeglarskie potwierdzone dyplomem.</p>",
        "program":"<ul><li>Szkolenie żeglarskie — manewry, węzły, teoria</li><li>Spływy kajakowe szlakami mazurskimi</li><li>Nauka pływania na desce SUP</li><li>Podstawy ratownictwa wodnego</li><li>Rejs całodniowy z biwakiem</li><li>Wieczory szantowe i ogniska nad jeziorem</li></ul>",
        "priceInclude":"<ul><li>Zakwaterowanie w centrum żeglarskim (9 dni / 8 nocy)</li><li>Pełne wyżywienie — 4 posiłki dziennie</li><li>Szkolenie żeglarskie i sprzęt wodny</li><li>Kajaki, deski SUP, kamizelki asekuracyjne</li><li>Opieka instruktorów i ratowników</li><li>Ubezpieczenie NNW i dyplom</li></ul>",
    },
    "contact":CONTACTS[2],
    "custom":[
        {"key":"poziom_trudnosci","label":"Poziom trudności","value":"Od podstaw"},
        {"key":"flota","label":"Sprzęt","value":"Jachty, kajaki, deski SUP w cenie"},
    ],
    "sessions": [
        {"from":S9["A"][0],"to":S9["A"][1],"days":9,"price":329000,"transport":"bus","bus_cities":["Kraków","Katowice"]},
        {"from":S9["B"][0],"to":S9["B"][1],"days":9,"price":329000,"transport":"bus","bus_cities":["Kraków","Wrocław"]},
        {"from":S9["C"][0],"to":S9["C"][1],"days":9,"price":329000,"transport":"own"},
        {"from":S9["E"][0],"to":S9["E"][1],"days":9,"price":329000,"transport":"bus","bus_cities":["Kraków","Katowice","Wrocław"]},
        {"from":S9["G"][0],"to":S9["G"][1],"days":9,"price":329000,"transport":"own"},
    ],
}))

# 12 Bike & Trail — Bieszczady (8-day, 4 turnusy)
events.append(build_event({
    "class":"YOUTH_CAMP","proc":"Obóz rowerowy","name":"Bike & Trail — Bieszczady 2026",
    "minAge":12,"maxAge":16,"profile":["active","góry","sport"],
    "loc":LOC_MYCZKOWCE,"lead":"https://camps4you.pl/tempOpen/images/offer-list-39-f-small-60-40-75-1.webp",
    "desc":{
        "general":"<p>Bike & Trail to obóz rowerowy MTB dla młodzieży, która chce poczuć smak bieszczadzkich tras. Bazą jest ośrodek nad zalewem Myczkowieckim, skąd codziennie wyruszamy na wyprawy po malowniczych szlakach i singletrackach.</p><p>Pod okiem instruktorów uczestnicy doskonalą technikę jazdy MTB, poznają podstawy enduro i uczą się orientacji w terenie. Trasy dobieramy do poziomu grupy, a serwis rowerowy i sprzęt ochronny zapewniają bezpieczeństwo każdej wyprawy.</p><p>Po dniu w siodle czeka regeneracja nad wodą, wspólne posiłki i wieczorne ogniska. Bike & Trail to obóz dla miłośników dwóch kółek, którzy chcą połączyć sport, przygodę i piękno Bieszczadów.</p>",
        "program":"<ul><li>Codzienne wyprawy MTB po bieszczadzkich szlakach</li><li>Technika jazdy: pokonywanie przeszkód, zjazdy, podjazdy</li><li>Podstawy enduro i bike parku</li><li>Orientacja w terenie i czytanie mapy</li><li>Serwis i podstawy naprawy roweru</li><li>Regeneracja nad zalewem i ogniska</li></ul>",
        "priceInclude":"<ul><li>Zakwaterowanie nad zalewem (8 dni / 7 nocy)</li><li>Pełne wyżywienie — 4 posiłki dziennie</li><li>Instruktorzy MTB i serwis rowerowy</li><li>Wypożyczenie roweru, kasku i ochraniaczy</li><li>Opieka wychowawców</li><li>Ubezpieczenie NNW</li></ul>",
    },
    "contact":CONTACTS[3],
    "extra_take":"<li>własny rower MTB (opcjonalnie — możliwość wypożyczenia)</li><li>rękawiczki rowerowe i okulary</li>",
    "custom":[
        {"key":"poziom_trudnosci","label":"Poziom trudności","value":"Średniozaawansowany"},
        {"key":"rower","label":"Rower","value":"Możliwość wypożyczenia na miejscu"},
    ],
    "sessions": sess_custom([S8["A"],S8["C"],S8["E"],S8["G"]], 8, 299000,
        ["bus","own","bus","own"]),
}))

# 13 Taternik Junior — Zakopane (9-day)
events.append(build_event({
    "class":"YOUTH_CAMP","proc":"Obóz górski","name":"Taternik Junior — Zakopane 2026",
    "minAge":11,"maxAge":16,"profile":["active","góry","przygoda"],
    "loc":LOC_ZAKOPANE,"lead":"https://camps4you.pl/tempOpen/images/offer-list-65-f-small-60-40-75-1.webp",
    "video":yt(),
    "desc":{
        "general":"<p>Taternik Junior to obóz górski dla młodzieży marzącej o tatrzańskich szczytach i pionowych ścianach. Baza w Zakopanem, u samego wejścia w Tatry, daje bezpośredni dostęp do szlaków, ścianek wspinaczkowych i tras via ferrata.</p><p>Pod okiem przewodników i instruktorów wspinaczki uczestnicy uczą się asekuracji, technik wspinaczkowych i bezpiecznego poruszania się w górach. Program obejmuje wspinaczkę skalną, łatwe via ferraty i wycieczki na tatrzańskie szczyty dostosowane do wieku grupy.</p><p>Bezpieczeństwo jest absolutnym priorytetem — pełen sprzęt, asekuracja i doświadczona kadra to standard. Taternik Junior kształtuje odwagę, wytrwałość i szacunek do gór, a uczestnicy wracają z realnymi umiejętnościami i niezapomnianymi widokami.</p>",
        "program":"<ul><li>Wspinaczka skalna z nauką asekuracji</li><li>Łatwe trasy via ferrata pod okiem instruktora</li><li>Wycieczki na tatrzańskie szczyty (dostosowane do wieku)</li><li>Podstawy bezpieczeństwa w górach i pierwsza pomoc</li><li>Orientacja w terenie górskim</li><li>Wieczory z opowieściami o Tatrach i ogniska</li></ul>",
        "priceInclude":"<ul><li>Zakwaterowanie w bazie górskiej (9 dni / 8 nocy)</li><li>Pełne wyżywienie — 4 posiłki dziennie</li><li>Przewodnicy i instruktorzy wspinaczki</li><li>Pełen sprzęt wspinaczkowy i asekuracyjny</li><li>Bilety wstępu (TPN) wg programu</li><li>Ubezpieczenie NNW</li></ul>",
    },
    "contact":CONTACTS[0],
    "extra_take":"<li>wysokie buty trekkingowe (dobrze rozchodzone)</li><li>kijki trekkingowe (opcjonalnie)</li>",
    "custom":[
        {"key":"poziom_trudnosci","label":"Poziom trudności","value":"Średniozaawansowany — wymaga kondycji"},
        {"key":"kadra","label":"Kadra","value":"Przewodnicy tatrzańscy i instruktorzy wspinaczki"},
    ],
    "sessions": sess9(["A","B","C","E","G"], 309000, ["bus","own","bus","own","bus"]),
}))

# 14 Arena Sportu — Krasnobród (9-day, Kraków+Katowice)
events.append(build_event({
    "class":"YOUTH_CAMP","proc":"Obóz sportowy","name":"Arena Sportu — Krasnobród 2026",
    "minAge":8,"maxAge":14,"profile":["active","sport"],
    "loc":LOC_KRASNOBROD,"lead":"https://camps4you.pl/tempOpen/images/offer-list-79-f-small-60-40-75-1.webp",
    "desc":{
        "general":"<p>Arena Sportu to wielodyscyplinowy obóz sportowy w uzdrowiskowym Krasnobrodzie na Roztoczu. Pełnowymiarowe boiska, hala i zalew tworzą idealną bazę dla młodych sportowców, którzy chcą spróbować swoich sił w wielu dyscyplinach.</p><p>Program ma formułę turnieju multisport: piłka nożna, koszykówka, siatkówka, tenis stołowy i lekkoatletyka. Uczestnicy rywalizują w drużynach, zdobywają punkty i uczą się zasad fair play pod okiem trenerów poszczególnych dyscyplin.</p><p>Krasnobrodzkie czyste powietrze i zaplecze sportowe sprzyjają zarówno rywalizacji, jak i regeneracji. Arena Sportu rozwija sprawność, ducha zespołu i zdrową rywalizację, a wielki finał turnieju i wręczenie medali to emocje, które zostają na długo.</p>",
        "program":"<ul><li>Turniej piłki nożnej i koszykówki</li><li>Siatkówka, tenis stołowy, badminton</li><li>Lekkoatletyka i tor sprawnościowy</li><li>Trening ogólnorozwojowy z trenerami</li><li>Kąpiele w zalewie i gry wodne</li><li>Wielki finał turnieju i wręczenie medali</li></ul>",
        "priceInclude":"<ul><li>Zakwaterowanie w ośrodku sportowym (9 dni / 8 nocy)</li><li>Pełne wyżywienie — 4 posiłki dziennie</li><li>Trenerzy poszczególnych dyscyplin</li><li>Sprzęt sportowy i dostęp do obiektów</li><li>Medale i nagrody dla uczestników</li><li>Ubezpieczenie NNW</li></ul>",
    },
    "contact":CONTACTS[1],
    "custom":[
        {"key":"poziom_trudnosci","label":"Poziom trudności","value":"Dla każdego — różne poziomy"},
        {"key":"dyscypliny","label":"Dyscypliny","value":"Piłka nożna, koszykówka, siatkówka, lekkoatletyka"},
    ],
    "sessions": [
        {"from":S9["A"][0],"to":S9["A"][1],"days":9,"price":259000,"transport":"bus","bus_cities":["Kraków","Katowice"]},
        {"from":S9["B"][0],"to":S9["B"][1],"days":9,"price":259000,"transport":"own"},
        {"from":S9["C"][0],"to":S9["C"][1],"days":9,"price":259000,"transport":"bus","bus_cities":["Kraków","Katowice"]},
        {"from":S9["D"][0],"to":S9["D"][1],"days":9,"price":259000,"transport":"own"},
        {"from":S9["F"][0],"to":S9["F"][1],"days":9,"price":259000,"transport":"bus","bus_cities":["Kraków"]},
    ],
}))

# 15 Wakeboard Camp — Przylasek Rusiecki (5-day day camp, own only, REGULAR_CAMP)
events.append(build_event({
    "class":"REGULAR_CAMP","proc":"Obóz sportowy","name":"Wakeboard Camp — Przylasek Rusiecki 2026",
    "minAge":10,"maxAge":16,"profile":["active","sport","woda"],
    "loc":LOC_RUSIECKI,"lead":"https://camps4you.pl/tempOpen/images/offer-list-95-f-small-60-40-75-1.webp",
    "desc":{
        "general":"<p>Wakeboard Camp to półkolonia w formule dziennej nad zalewem Przylasek Rusiecki w granicach Krakowa. To idealna propozycja dla dzieci i młodzieży, które chcą opanować wakeboarding bez wyjeżdżania z miasta — rano przygoda na wodzie, wieczorem powrót do domu.</p><p>Pod okiem instruktorów uczestnicy uczą się jazdy na wyciągu nart wodnych, od pierwszych przejazdów po pierwsze skoki. Sprzęt — deski, kamizelki, kaski — jest w cenie i dobrany do poziomu. Naukę uzupełniają gry plażowe i sporty wodne.</p><p>Dzień zaczyna się o poranku i kończy po południu, z pełną opieką, posiłkiem i bezpieczną asekuracją na wodzie. Wakeboard Camp to wakacyjna dawka adrenaliny na wyciągnięcie ręki, idealna dla mieszkańców Krakowa i okolic.</p>",
        "program":"<ul><li>Codzienne sesje wakeboardingu na wyciągu</li><li>Nauka od podstaw: start, balans, pierwsze przejazdy</li><li>Doskonalenie techniki i pierwsze skoki</li><li>Gry plażowe i sporty wodne</li><li>Zajęcia na świeżym powietrzu i integracja</li><li>Mini-zawody na zakończenie tygodnia</li></ul>",
        "priceInclude":"<ul><li>Opieka instruktorów i wychowawców (5 dni, formuła dzienna)</li><li>Obiad i podwieczorek każdego dnia</li><li>Sesje wakeboardingu na wyciągu</li><li>Sprzęt: deski, kamizelki, kaski, pianki</li><li>Asekuracja i opieka ratownika</li><li>Ubezpieczenie NNW</li></ul>",
    },
    "contact":CONTACTS[2],
    "diet":"obiad i podwieczorek",
    "extra_take":"<li>strój kąpielowy i ręcznik</li><li>obuwie na zmianę i krem z filtrem</li>",
    "custom":[
        {"key":"poziom_trudnosci","label":"Poziom trudności","value":"Od podstaw"},
        {"key":"formula","label":"Formuła","value":"Półkolonia dzienna (bez noclegów)"},
    ],
    "sessions": [
        {"from":S5["W2"][0],"to":S5["W2"][1],"days":5,"price":129000,"transport":"own","room":"Bez noclegu (formuła dzienna)"},
        {"from":S5["W4"][0],"to":S5["W4"][1],"days":5,"price":129000,"transport":"own","room":"Bez noclegu (formuła dzienna)"},
        {"from":S5["W6"][0],"to":S5["W6"][1],"days":5,"price":129000,"transport":"own","room":"Bez noclegu (formuła dzienna)"},
        {"from":S5["W8"][0],"to":S5["W8"][1],"days":5,"price":129000,"transport":"own","room":"Bez noclegu (formuła dzienna)"},
    ],
}))

# 16 English in the Mountains — Bukowina (9-day, 4 turnusy)
events.append(build_event({
    "class":"YOUTH_CAMP","proc":"Obóz językowy","name":"English in the Mountains — Bukowina Tatrzańska 2026",
    "minAge":12,"maxAge":17,"tags":["nowość"],"profile":["język","chill","góry"],
    "loc":LOC_STASINDA,"lead":"https://camps4you.pl/tempOpen/images/offer-list-87-f-small-60-40-75-1.webp",
    "desc":{
        "general":"<p>English in the Mountains to obóz językowy dla młodzieży, który łączy naukę angielskiego z górskimi przygodami w Bukowinie Tatrzańskiej. To propozycja dla nastolatków chcących płynnie posługiwać się językiem w realnych sytuacjach.</p><p>Codziennie odbywa się 5 bloków po 90 minut zajęć prowadzonych przez doświadczonych lektorów metodą komunikacyjną. Stawiamy na konwersacje, projekty i praktyczne użycie języka — także podczas wycieczek górskich i zajęć sportowych, gdzie angielski staje się naturalnym narzędziem.</p><p>Po lekcjach czekają wyprawy w Tatry, sport i baseny termalne, a wieczorami English evenings i gry językowe. Uczestnicy kończą turnus z większą swobodą w mówieniu, certyfikatem i wspomnieniami z gór.</p>",
        "program":"<ul><li>5 bloków językowych × 90 min dziennie</li><li>Konwersacje i projekty w małych grupach</li><li>English evenings — gry i quizy językowe</li><li>Wycieczki górskie z elementami języka</li><li>Sport i baseny termalne</li><li>Test poziomujący i certyfikat ukończenia</li></ul>",
        "priceInclude":"<ul><li>Zakwaterowanie w GRAND STASINDA (9 dni / 8 nocy)</li><li>Pełne wyżywienie — 4 posiłki dziennie</li><li>Kurs angielskiego (5 × 90 min dziennie) z lektorem</li><li>Materiały dydaktyczne i certyfikat</li><li>Wstęp na baseny termalne i wycieczki</li><li>Ubezpieczenie NNW</li></ul>",
    },
    "contact":CONTACTS[3],
    "custom":[
        {"key":"poziom_trudnosci","label":"Poziom językowy","value":"Grupy A2–B2 (test poziomujący)"},
        {"key":"godziny_jezyka","label":"Wymiar zajęć","value":"5 bloków × 90 min dziennie"},
    ],
    "sessions": sess9(["B","C","E","F"], 349000, ["bus","own","bus","own"]),
}))

# 17 Letnie Aktywne Półkolonie — Kraków (5-day day camp, own, 6 turnusy, REGULAR_CAMP)
events.append(build_event({
    "class":"REGULAR_CAMP","proc":"Półkolonie","name":"Letnie Aktywne Półkolonie — Kraków 2026",
    "minAge":5,"maxAge":13,"profile":["active","sport"],
    "loc":LOC_KORONA,"lead":"https://camps4you.pl/tempOpen/images/offer-list-94-f-small-60-40-75-1.webp",
    "desc":{
        "general":"<p>Letnie Aktywne Półkolonie to miejskie wakacje w sercu Krakowa dla dzieci, których rodzice pracują w wakacje. W kompleksie sportowym KS Korona przy ul. Kalwaryjskiej dzieci spędzają aktywny, wartościowy dzień pod opieką doświadczonych wychowawców.</p><p>Każdy dzień to porcja ruchu i nauki: pływanie na basenie, zajęcia sportowe na boiskach i hali, warsztaty robotyki oraz wycieczki po Krakowie i okolicach. Program dopasowujemy do wieku, tak by zarówno przedszkolaki, jak i starsze dzieci znalazły coś dla siebie.</p><p>Półkolonie trwają od rana do popołudnia, z dwoma posiłkami i pełną opieką. To wygodne i bezpieczne rozwiązanie dla rodzin z Krakowa, które chcą zapewnić dziecku aktywne i ciekawe wakacje bez wyjazdu z miasta.</p>",
        "program":"<ul><li>Codzienne pływanie na basenie pod okiem ratownika</li><li>Zajęcia sportowe na boiskach i w hali</li><li>Warsztaty robotyki i programowania</li><li>Wycieczki po Krakowie i okolicach</li><li>Gry i zabawy integracyjne</li><li>Zajęcia plastyczne i kreatywne</li></ul>",
        "priceInclude":"<ul><li>Opieka wychowawców (5 dni, od 8:00 do 16:30)</li><li>Obiad i podwieczorek każdego dnia</li><li>Wstęp na basen i obiekty sportowe</li><li>Warsztaty robotyki i materiały</li><li>Bilety wstępu na wycieczki</li><li>Ubezpieczenie NNW</li></ul>",
    },
    "contact":CONTACTS[0],
    "diet":"obiad i podwieczorek",
    "extra_take":"<li>strój kąpielowy, czepek i ręcznik</li><li>obuwie sportowe na zmianę</li><li>plecak na wycieczki i bidon</li>",
    "custom":[
        {"key":"poziom_trudnosci","label":"Poziom trudności","value":"Dla każdego"},
        {"key":"formula","label":"Formuła","value":"Półkolonia dzienna 8:00–16:30"},
    ],
    "sessions": [
        {"from":S5["W1"][0],"to":S5["W1"][1],"days":5,"price":109000,"transport":"own","room":"Bez noclegu (formuła dzienna)"},
        {"from":S5["W2"][0],"to":S5["W2"][1],"days":5,"price":109000,"transport":"own","room":"Bez noclegu (formuła dzienna)"},
        {"from":S5["W3"][0],"to":S5["W3"][1],"days":5,"price":109000,"transport":"own","room":"Bez noclegu (formuła dzienna)"},
        {"from":S5["W5"][0],"to":S5["W5"][1],"days":5,"price":109000,"transport":"own","room":"Bez noclegu (formuła dzienna)"},
        {"from":S5["W7"][0],"to":S5["W7"][1],"days":5,"price":109000,"transport":"own","room":"Bez noclegu (formuła dzienna)"},
        {"from":S5["W9"][0],"to":S5["W9"][1],"days":5,"price":109000,"transport":"own","room":"Bez noclegu (formuła dzienna)"},
    ],
}))

# 18 Family Summer Camp — Bukowina (9-day, 3 turnusy: July + 2x August, FAMILY_CAMP)
events.append(build_event({
    "class":"FAMILY_CAMP","proc":"Obóz rodzinny","name":"Family Summer Camp — Bukowina Tatrzańska 2026",
    "minAge":6,"maxAge":99,"profile":["chill","active","rodzinny"],
    "loc":LOC_STASINDA,"lead":"https://camps4you.pl/tempOpen/images/offer-list-96-f-small-60-40-75-1.webp",
    "desc":{
        "general":"<p>Family Summer Camp to wakacje dla całej rodziny w górskim centrum GRAND STASINDA w Bukowinie Tatrzańskiej. To wyjątkowa propozycja, w której rodzice i dzieci spędzają czas razem, a jednocześnie każde pokolenie znajduje atrakcje skrojone na własne potrzeby.</p><p>Program oferuje wspólne wycieczki górskie dostosowane tempem do rodzin, baseny termalne, animacje dla dzieci i strefę relaksu dla dorosłych. Animatorzy organizują zajęcia dla najmłodszych, dzięki czemu rodzice mają również chwilę dla siebie. Wieczory to wspólne ogniska, gry planszowe i koncerty.</p><p>Komfortowe rodzinne pokoje, elastyczny plan dnia i pełne wyżywienie sprawiają, że to wakacje bez stresu organizacyjnego. Family Summer Camp to czas, by zacieśnić rodzinne więzi w pięknej tatrzańskiej scenerii — bez kompromisów dla żadnego pokolenia.</p>",
        "program":"<ul><li>Wspólne wycieczki górskie w tempie rodzinnym</li><li>Codzienny wstęp na baseny termalne</li><li>Animacje i warsztaty dla dzieci</li><li>Strefa relaksu i wellness dla dorosłych</li><li>Gry rodzinne, turnieje i ogniska</li><li>Wieczory z muzyką na żywo</li></ul>",
        "priceInclude":"<ul><li>Zakwaterowanie w rodzinnych pokojach (9 dni / 8 nocy)</li><li>Pełne wyżywienie — 4 posiłki dziennie</li><li>Animacje dla dzieci i opieka animatorów</li><li>Codzienny wstęp na baseny termalne</li><li>Program wycieczek i atrakcji</li><li>Ubezpieczenie NNW (cena za osobę)</li></ul>",
    },
    "contact":CONTACTS[1],
    "diet":"rodzinna (różne grupy wiekowe)",
    "custom":[
        {"key":"poziom_trudnosci","label":"Poziom trudności","value":"Dla całej rodziny"},
        {"key":"cena_za_osobe","label":"Cena","value":"Podana za osobę (dorosły i dziecko)"},
    ],
    "sessions": [
        {"from":S9["C"][0],"to":S9["C"][1],"days":9,"price":299000,"transport":"bus","room":"Pokój rodzinny 4-osobowy z łazienką"},
        {"from":S9["F"][0],"to":S9["F"][1],"days":9,"price":299000,"transport":"own","room":"Pokój rodzinny 4-osobowy z łazienką"},
        {"from":S9["G"][0],"to":S9["G"][1],"days":9,"price":299000,"transport":"bus","room":"Pokój rodzinny 4-osobowy z łazienką"},
    ],
}))

# ---------------- Winter 2027 ----------------
# Winter slots (8-day)
W27 = {
    "J25": ("2027-01-25","2027-02-01"),
    "F01": ("2027-02-01","2027-02-08"),
    "F08": ("2027-02-08","2027-02-15"),
    "F15": ("2027-02-15","2027-02-22"),
}

# 19 Kids Winter Camp — Bukowina STASINDA (3 turnusy: Feb 1-8, 8-15, 15-22, REGULAR_CAMP, Kolonia zimowa)
events.append(build_event({
    "class":"REGULAR_CAMP","proc":"Kolonia zimowa","name":"Kids Winter Camp — Bukowina Tatrzańska 2027",
    "minAge":6,"maxAge":11,"profile":["zimowy","sport"],
    "loc":LOC_STASINDA,"lead":"https://camps4you.pl/tempOpen/images/offer-list-3-f-small-60-40-75-1.webp",
    "desc":{
        "general":"<p>Kids Winter Camp to zimowa kolonia dla najmłodszych w Bukowinie Tatrzańskiej, gdzie dzieci stawiają pierwsze kroki na nartach w bezpiecznej, troskliwej atmosferze. Centrum STASINDA z basenami termalnymi i strefą GymSpace to idealna baza zimowego wypoczynku.</p><p>Sercem programu jest szkółka narciarska prowadzona przez licencjonowanych instruktorów, dostosowana do poziomu i wieku każdego dziecka. Po nartach czeka GymSpace — strefa zabaw ruchowych, baseny termalne i czas wolny wypełniony grami i animacjami.</p><p>Wzmożona opieka, ciepłe posiłki i kameralne grupy sprawiają, że nawet najmłodsi czują się bezpiecznie z dala od domu. Kids Winter Camp to pierwsza zimowa przygoda, z której dzieci wracają z umiejętnością jazdy na nartach i uśmiechem.</p>",
        "program":"<ul><li>Codzienna szkółka narciarska z instruktorem</li><li>Strefa GymSpace — zabawy ruchowe i tor przeszkód</li><li>Kąpiele w basenach termalnych</li><li>Animacje, gry i zabawy w śniegu</li><li>Kuligi i zabawy zimowe</li><li>Wieczory bajkowe i konkursy</li></ul>",
        "priceInclude":"<ul><li>Zakwaterowanie w centrum STASINDA (8 dni / 7 nocy)</li><li>Pełne wyżywienie — 4 posiłki dziennie</li><li>Szkółka narciarska z instruktorem</li><li>Wstęp do strefy GymSpace i basenów termalnych</li><li>Wzmożona opieka troskliwej kadry</li><li>Ubezpieczenie NNW (skipass płatny dodatkowo)</li></ul>",
    },
    "contact":CONTACTS[2],
    "diet":"lekkostrawna dla dzieci",
    "extra_take":"<li>kombinezon narciarski, kask i gogle</li><li>ciepłe rękawice i buty zimowe</li><li>termobielizna i ciepłe skarpety</li>",
    "custom":[
        {"key":"poziom_trudnosci","label":"Poziom trudności","value":"Od podstaw — pierwsze narty"},
        {"key":"skipass","label":"Skipass","value":"Dopłata 620 zł (płatne na miejscu)"},
    ],
    "sessions": [
        {"from":W27["F01"][0],"to":W27["F01"][1],"days":8,"price":279000,"transport":"bus","price_note":"Skipass: 620 zł","room":"Pokoje 4-osobowe z łazienką"},
        {"from":W27["F08"][0],"to":W27["F08"][1],"days":8,"price":279000,"transport":"bus","price_note":"Skipass: 620 zł","room":"Pokoje 4-osobowe z łazienką"},
        {"from":W27["F15"][0],"to":W27["F15"][1],"days":8,"price":279000,"transport":"bus","price_note":"Skipass: 620 zł","room":"Pokoje 4-osobowe z łazienką"},
    ],
}))

# 20 Snow Camp — Bukowina SKALNITY (4 turnusy: Jan25-Feb1, Feb1-8, 8-15, 15-22, YOUTH_CAMP, Obóz zimowy)
events.append(build_event({
    "class":"YOUTH_CAMP","proc":"Obóz zimowy","name":"Snow Camp — Bukowina Tatrzańska 2027",
    "minAge":12,"maxAge":17,"tags":["hit sezonu"],"profile":["zimowy","sport","active"],
    "loc":LOC_SKALNITY,"lead":"https://camps4you.pl/tempOpen/images/offer-list-7-f-small-60-40-75-1.webp",
    "video":yt(),
    "desc":{
        "general":"<p>Snow Camp to zimowy obóz narciarsko-snowboardowy dla młodzieży w Bukowinie Tatrzańskiej, z bazą w hotelu SKALNITY u stóp wyciągów. To propozycja dla nastolatków, którzy chcą szlifować technikę zjazdu i poczuć prawdziwą zimową atmosferę gór.</p><p>Szkolenie narciarskie i snowboardowe prowadzą licencjonowani instruktorzy, w grupach dobranych według poziomu — od średniozaawansowanych po zaawansowanych. Program wzbogacają wyprawy na rakietach śnieżnych, zabawy w śniegu i wieczorne strefy après-ski z muzyką i grami.</p><p>Komfortowy hotel z basenem i strefą wellness zapewnia regenerację po dniu na stoku. Snow Camp to intensywne, sportowe ferie pełne adrenaliny, świeżego śniegu i nowych znajomości — zimowy hit naszej oferty.</p>",
        "program":"<ul><li>Codzienne szkolenie narciarskie lub snowboardowe</li><li>Doskonalenie techniki w grupach wg poziomu</li><li>Wyprawy na rakietach śnieżnych</li><li>Zabawy zimowe i konkursy na stoku</li><li>Strefa wellness i basen w hotelu</li><li>Wieczory après-ski z muzyką i grami</li></ul>",
        "priceInclude":"<ul><li>Zakwaterowanie w hotelu SKALNITY (8 dni / 7 nocy)</li><li>Pełne wyżywienie — 4 posiłki dziennie</li><li>Szkolenie narciarskie/snowboardowe z instruktorem</li><li>Rakiety śnieżne i sprzęt na wyprawy</li><li>Wstęp na basen i do strefy wellness</li><li>Ubezpieczenie NNW (skipass płatny dodatkowo)</li></ul>",
    },
    "contact":CONTACTS[3],
    "diet":"standardowa",
    "extra_take":"<li>narty lub deska snowboardowa (możliwość wypożyczenia)</li><li>kask, gogle i kombinezon narciarski</li><li>termobielizna i ciepłe rękawice</li>",
    "custom":[
        {"key":"poziom_trudnosci","label":"Poziom trudności","value":"Średniozaawansowany i zaawansowany"},
        {"key":"skipass","label":"Skipass","value":"Dopłata 620 zł (płatne na miejscu)"},
    ],
    "sessions": [
        {"from":W27["J25"][0],"to":W27["J25"][1],"days":8,"price":329000,"transport":"bus","bus_cities":["Kraków","Katowice"],"price_note":"Skipass: 620 zł"},
        {"from":W27["F01"][0],"to":W27["F01"][1],"days":8,"price":329000,"transport":"bus","bus_cities":["Kraków"],"price_note":"Skipass: 620 zł"},
        {"from":W27["F08"][0],"to":W27["F08"][1],"days":8,"price":329000,"transport":"bus","bus_cities":["Kraków","Katowice"],"price_note":"Skipass: 620 zł"},
        {"from":W27["F15"][0],"to":W27["F15"][1],"days":8,"price":329000,"transport":"bus","bus_cities":["Kraków"],"price_note":"Skipass: 620 zł"},
    ],
}))

# Wire bus_cities through sess_custom items that carry them (override default builder)
# (handled above via make_session bus_cities param when present)

# Apply bus_cities for sessions that declared them in dicts processed by build_event:
# build_event passes sc.get("bus_cities") already. Good.

with open("D:/git/campsflow.pl-wp/tests/fixtures/seed-events.json","w",encoding="utf-8") as f:
    json.dump(events, f, ensure_ascii=False, indent=2)

# stats
total_sessions = sum(len(e["turnusy"]) for e in events)
print(f"EVENTS={len(events)} SESSIONS={total_sessions}")
