"""
Generator animasi Lottie kyread (format Bodymovin/LottieFiles, dipakai web & Android).

    python resources/lottie/generate.py

Animasi dibuat dari bentuk vektor sederhana dengan warna merek, jadi bebas lisensi dan
ringan (< 15 KB). Untuk memakai animasi dari lottiefiles.com, cukup timpa file JSON
dengan nama yang sama (loading, empty, success, error).
"""
import json
import math
import os

HERE = os.path.dirname(os.path.abspath(__file__))


def hex_color(h, alpha=1):
    h = h.lstrip('#')
    return [round(int(h[i:i + 2], 16) / 255, 4) for i in (0, 2, 4)] + [alpha]


BLUE, BLUE_DARK, BLUE_LIGHT = hex_color('#43aaff'), hex_color('#1f8ae6'), hex_color('#cfe6ff')
INK, WHITE, GREEN, RED, AMBER = hex_color('#1f2937'), hex_color('#ffffff'), hex_color('#22c55e'), hex_color('#ef4444'), hex_color('#f59e0b')

EASE = {'i': {'x': 0.4, 'y': 1}, 'o': {'x': 0.6, 'y': 0}}


# --- Nilai statis / beranimasi ---------------------------------------------------------
def static(v):
    return {'a': 0, 'k': v}


def anim(frames, hold=False):
    """frames: [(t, value), ...] -> properti beranimasi dengan easing halus."""
    keys = []
    for idx, (t, v) in enumerate(frames):
        v = v if isinstance(v, list) else [v]
        k = {'t': t, 's': v}
        if idx < len(frames) - 1:
            k.update(EASE)
            if hold:
                k['h'] = 1
        keys.append(k)
    return {'a': 1, 'k': keys}


# --- Bentuk ----------------------------------------------------------------------------
def rect(w, h, x=0, y=0, r=0):
    return {'ty': 'rc', 'd': 1, 's': static([w, h]), 'p': static([x, y]), 'r': static(r), 'nm': 'rect'}


def ellipse(w, h, x=0, y=0):
    return {'ty': 'el', 'd': 1, 's': static([w, h]), 'p': static([x, y]), 'nm': 'ellipse'}


def path(points, closed=False, ins=None, outs=None):
    n = len(points)
    return {'ty': 'sh', 'ks': static({'i': ins or [[0, 0]] * n, 'o': outs or [[0, 0]] * n, 'v': points, 'c': closed}), 'nm': 'path'}


def fill(color, opacity=100):
    return {'ty': 'fl', 'c': static(color), 'o': static(opacity), 'r': 1, 'nm': 'fill'}


def stroke(color, width, opacity=100):
    return {'ty': 'st', 'c': static(color), 'o': static(opacity), 'w': static(width), 'lc': 2, 'lj': 2, 'nm': 'stroke'}


def trim(end):
    return {'ty': 'tm', 's': static(0), 'e': end, 'o': static(0), 'm': 1, 'nm': 'trim'}


def group(*items):
    tr = {'ty': 'tr', 'p': static([0, 0]), 'a': static([0, 0]), 's': static([100, 100]), 'r': static(0),
          'o': static(100), 'sk': static(0), 'sa': static(0), 'nm': 'transform'}
    return {'ty': 'gr', 'it': list(items) + [tr], 'nm': 'group'}


def layer(name, shapes, op, p=(0, 0), a=(0, 0), s=None, r=None, o=None, ip=0):
    return {
        'ddd': 0, 'ty': 4, 'nm': name, 'sr': 1, 'ao': 0, 'bm': 0, 'ip': ip, 'op': op, 'st': 0,
        'ks': {
            'o': o or static(100),
            'r': r or static(0),
            'p': p if isinstance(p, dict) else static([p[0], p[1], 0]),
            'a': static([a[0], a[1], 0]),
            's': s or static([100, 100, 100]),
        },
        'shapes': shapes,
    }


def comp(name, w, h, op, layers, fr=60):
    for i, l in enumerate(layers, 1):
        l['ind'] = i
    return {'v': '5.7.4', 'fr': fr, 'ip': 0, 'op': op, 'w': w, 'h': h, 'nm': name, 'ddd': 0, 'assets': [], 'layers': layers}


# --- 1. loading: buku terbuka yang halamannya membalik ------------------------------------
def loading():
    op = 96
    layers = []
    for i in range(3):
        start = 8 + i * 22
        layers.append(layer(
            f'page {i + 1}', [group(rect(50, 70, 29, 0, 4), fill(WHITE), stroke(BLUE_LIGHT, 3))], op,
            p=(100, 104),
            s=anim([(0, [100, 100, 100]), (start, [100, 100, 100]), (start + 30, [-100, 100, 100])]),
            o=anim([(0, 100), (82, 100), (92, 0)]),
        ))
    layers += [
        layer('cover right', [group(rect(62, 82, 131, 104, 7), fill(BLUE))], op),
        layer('cover left', [group(rect(62, 82, 69, 104, 7), fill(BLUE_DARK))], op),
        layer('shadow', [group(ellipse(120, 14, 0, 0), fill(INK, 12))], op, p=(100, 162),
              s=anim([(0, [100, 100, 100]), (48, [86, 100, 100]), (96, [100, 100, 100])])),
    ]
    return comp('loading', 200, 200, op, layers)


# --- 2. empty: kaca pembesar menyisir tumpukan buku ---------------------------------------
def empty():
    op = 150
    dust = []
    for i, (x, y) in enumerate([(86, 92), (126, 84), (156, 96)]):
        t0 = i * 30
        dust.append(layer(f'dust {i}', [group(ellipse(8, 8), fill(BLUE, 60))], op, p=(x, y),
                          o=anim([(t0, 0), (t0 + 25, 100), (t0 + 55, 0)]),
                          s=anim([(t0, [40, 40, 100]), (t0 + 55, [110, 110, 100])])))
    glass = layer(
        'magnifier',
        [group(path([[15, 15], [32, 32]]), stroke(INK, 9)),
         group(ellipse(46, 46), fill(WHITE, 70), stroke(INK, 6))],
        op,
        p=anim([(0, [78, 70, 0]), (75, [162, 78, 0]), (150, [78, 70, 0])]),
        r=anim([(0, -8), (75, 8), (150, -8)]),
    )
    return comp('empty', 240, 200, op, [glass] + dust + [
        layer('book top', [group(rect(84, 18, 122, 122, 4), fill(BLUE)), group(rect(70, 4, 126, 122, 2), fill(WHITE, 70))], op),
        layer('book bottom', [group(rect(104, 22, 118, 143, 5), fill(BLUE_DARK)), group(rect(88, 4, 124, 143, 2), fill(WHITE, 60))], op),
        layer('shadow', [group(ellipse(150, 12), fill(INK, 10))], op, p=(120, 162)),
    ])


# --- 3. success: lingkaran hijau, centang tergambar, percikan ------------------------------
def success():
    op = 72
    burst = []
    for i in range(8):
        ang = math.radians(i * 45 + 22.5)
        # Mulai dari tepi lingkaran (r=52) agar tidak tertutup, lalu terlempar keluar.
        sx, sy = 100 + 52 * math.cos(ang), 100 + 52 * math.sin(ang)
        tx, ty = 100 + 92 * math.cos(ang), 100 + 92 * math.sin(ang)
        color = GREEN if i % 2 == 0 else BLUE
        burst.append(layer(f'spark {i}', [group(ellipse(12, 12), fill(color))], op,
                           p=anim([(16, [round(sx, 1), round(sy, 1), 0]), (44, [round(tx, 1), round(ty, 1), 0])]),
                           s=anim([(16, [0, 0, 100]), (26, [110, 110, 100]), (46, [0, 0, 100])])))
    check = layer('check', [group(path([[-24, 2], [-7, 19], [26, -16]]), stroke(WHITE, 11),
                                  trim(anim([(16, 0), (36, 100)])))], op, p=(100, 100))
    circle = layer('circle', [group(ellipse(112, 112), fill(GREEN))], op, p=(100, 100),
                   s=anim([(0, [0, 0, 100]), (14, [115, 115, 100]), (22, [100, 100, 100])]))
    return comp('success', 200, 200, op, [check, circle] + burst)


# --- 4. error: buku sedih bergoyang dengan tetes keringat --------------------------------
def error():
    op = 120
    face = group(
        group(ellipse(9, 9, -14, -12), fill(WHITE)),
        group(ellipse(9, 9, 14, -12), fill(WHITE)),
        group(path([[-14, 18], [14, 18]], ins=[[0, 0], [-8, -11]], outs=[[8, -11], [0, 0]]), stroke(WHITE, 5)),
    )
    book = layer('book', [face, group(rect(10, 92, -33, 0, 3), fill(BLUE_DARK)), group(rect(76, 92, 0, 0, 8), fill(BLUE))],
                 op, p=(110, 112), r=anim([(0, -7), (60, 7), (120, -7)]))
    drop = layer('drop', [group(path([[0, -9], [7, 4], [0, 10], [-7, 4]], closed=True,
                                     ins=[[0, 0], [0, -4], [4, 0], [0, 4]], outs=[[0, 0], [0, 4], [-4, 0], [0, -4]]),
                                fill(BLUE_LIGHT))],
                 op, p=anim([(10, [160, 58, 0]), (70, [166, 88, 0])]), o=anim([(10, 0), (25, 100), (60, 100), (72, 0)]))
    return comp('error', 220, 200, op, [drop, book, layer(
        'shadow', [group(ellipse(110, 12), fill(INK, 12))], op, p=(110, 170),
        s=anim([(0, [90, 100, 100]), (60, [105, 100, 100]), (120, [90, 100, 100])]))])


for name, data in {'loading': loading(), 'empty': empty(), 'success': success(), 'error': error()}.items():
    with open(os.path.join(HERE, f'{name}.json'), 'w', encoding='utf8', newline='\n') as f:
        json.dump(data, f, separators=(',', ':'))
    print(name, os.path.getsize(os.path.join(HERE, f'{name}.json')), 'bytes')
