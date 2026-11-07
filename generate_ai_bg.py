import math
import random
from PIL import Image, ImageDraw, ImageFilter

WIDTH, HEIGHT = 1920, 1080
image = Image.new("RGBA", (WIDTH, HEIGHT), (7, 11, 20, 255))
draw = ImageDraw.Draw(image)

# 1. Base dark gradient
for y in range(HEIGHT):
    t = y / HEIGHT
    # Gradient from deep dark navy (#070b14) to midnight indigo (#0f172a) to deep purple (#1e1b4b)
    r = int(7 * (1 - t) + 20 * t + 10 * math.sin(t * math.pi))
    g = int(11 * (1 - t) + 18 * t + 8 * math.sin(t * math.pi))
    b = int(20 * (1 - t) + 50 * t + 35 * math.sin(t * math.pi))
    draw.line([(0, y), (WIDTH, y)], fill=(r, g, b, 255))

# 2. Large ambient glowing orbs in background
ambient = Image.new("RGBA", (WIDTH, HEIGHT), (0, 0, 0, 0))
ambient_draw = ImageDraw.Draw(ambient)

orbs = [
    (350, 250, 400, (79, 70, 229, 65)),     # Indigo glow
    (1500, 300, 450, (56, 189, 248, 55)),   # Cyan glow
    (960, 750, 500, (147, 51, 234, 50)),    # Purple glow
    (200, 850, 350, (37, 99, 235, 60)),     # Blue glow
]

for ox, oy, radius, color in orbs:
    for rad in range(radius, 0, -15):
        alpha = int(color[3] * (1 - rad / radius)**1.5)
        ambient_draw.ellipse([ox - rad, oy - rad, ox + rad, oy + rad], fill=(color[0], color[1], color[2], alpha))

ambient = ambient.filter(ImageFilter.GaussianBlur(40))
image = Image.alpha_composite(image, ambient)
draw = ImageDraw.Draw(image)

# 3. Neural Network Layer Architecture
# Define nodes in 6 columns (representing Input -> Hidden 1 -> Hidden 2 -> Hidden 3 -> Hidden 4 -> Output)
random.seed(42)

layers = [
    # (x, count, y_spread, color)
    (180, 5, 750, (56, 189, 248)),     # Input layer (cyan)
    (480, 7, 850, (129, 140, 248)),    # Hidden 1 (soft indigo)
    (800, 8, 900, (168, 85, 247)),     # Hidden 2 (purple)
    (1120, 7, 850, (129, 140, 248)),   # Hidden 3 (soft indigo)
    (1440, 6, 800, (56, 189, 248)),    # Hidden 4 (cyan)
    (1740, 4, 650, (244, 114, 182)),   # Output layer (pink)
]

nodes = []
for col_idx, (col_x, count, spread, color) in enumerate(layers):
    col_nodes = []
    y_start = (HEIGHT - spread) / 2
    spacing = spread / (count - 1) if count > 1 else 0
    for i in range(count):
        # Add slight natural jitter
        nx = col_x + random.uniform(-15, 15)
        ny = y_start + i * spacing + random.uniform(-10, 10)
        col_nodes.append((nx, ny, color))
    nodes.append(col_nodes)

# 4. Draw Synaptic Connections (lines between adjacent layers)
lines_overlay = Image.new("RGBA", (WIDTH, HEIGHT), (0, 0, 0, 0))
lines_draw = ImageDraw.Draw(lines_overlay)

for l in range(len(nodes) - 1):
    curr_col = nodes[l]
    next_col = nodes[l + 1]
    for x1, y1, c1 in curr_col:
        for x2, y2, c2 in next_col:
            # 65% chance of connection
            if random.random() < 0.65:
                weight = random.uniform(0.15, 0.95)
                alpha = int(weight * 90)
                # Blend color
                lr = int((c1[0] + c2[0]) / 2)
                lg = int((c1[1] + c2[1]) / 2)
                lb = int((c1[2] + c2[2]) / 2)
                line_w = 2 if weight > 0.7 else 1
                lines_draw.line([(x1, y1), (x2, y2)], fill=(lr, lg, lb, alpha), width=line_w)

image = Image.alpha_composite(image, lines_overlay)
draw = ImageDraw.Draw(image)

# 5. Draw Glowing Nodes
glow_overlay = Image.new("RGBA", (WIDTH, HEIGHT), (0, 0, 0, 0))
glow_draw = ImageDraw.Draw(glow_overlay)

for col in nodes:
    for x, y, (cr, cg, cb) in col:
        # Outer soft glow
        for r in range(24, 0, -3):
            a = int(45 * (1 - r / 24))
            glow_draw.ellipse([x - r, y - r, x + r, y + r], fill=(cr, cg, cb, a))

glow_overlay = glow_overlay.filter(ImageFilter.GaussianBlur(3))
image = Image.alpha_composite(image, glow_overlay)
draw = ImageDraw.Draw(image)

for col in nodes:
    for x, y, (cr, cg, cb) in col:
        # Inner solid core
        draw.ellipse([x - 7, y - 7, x + 7, y + 7], fill=(cr, cg, cb, 230))
        # White center highlight
        draw.ellipse([x - 3, y - 3, x + 3, y + 3], fill=(255, 255, 255, 255))

# 6. Save as high-quality PNG and JPG
out_png = "c:/tsp/dist/demo-site-aisim/login_bg.png"
image.convert("RGB").save(out_png, "PNG", optimize=True)
print(f"Generated {out_png}")
