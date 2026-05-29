# 🎨 Design Features - DGSign System

## Visual Design Language

### Color Palette
```
Primary Gradient:   #667eea → #764ba2 (Purple-Blue)
Success:            #28a745 (Green)
Error:              #dc3545 (Red)
Warning:            #ffc107 (Yellow)
Info:               #17a2b8 (Cyan)
Background:         #f8f9fa (Light Gray)
Text:               #495057 (Dark Gray)
```

### Typography
- **Font Family**: Segoe UI, Tahoma, Geneva, Verdana, sans-serif
- **Header**: 2.5em, bold, white with text-shadow
- **Step Title**: 1.6em, purple gradient color
- **Body Text**: 15px, line-height 1.6

## Component Breakdown

### 1. Header Section
```
┌─────────────────────────────────────────────┐
│  🔒 DGSign Portal                           │
│  Sistem Tanda Tangan Digital STEI-ITB      │
│  Menggunakan Kriptografi PHP OpenSSL       │
│  [Gradient Background: Purple → Blue]       │
└─────────────────────────────────────────────┘
```

**Features:**
- Gradient background matching theme
- Centered text with shadow
- Slide-down animation on load

### 2. Message Boxes
```
┌─────────────────────────────────────────────┐
│ ┃ ✅ Sukses: OTP berhasil di-generate!     │
│ ┃ [Green background with left border]       │
└─────────────────────────────────────────────┘

┌─────────────────────────────────────────────┐
│ ┃ ❌ Error: NIM belum terdaftar!            │
│ ┃ [Red background with left border]         │
└─────────────────────────────────────────────┘

┌─────────────────────────────────────────────┐
│ ┃ ⚠️ Peringatan: Sudah punya sertifikat!   │
│ ┃ [Yellow background with left border]      │
└─────────────────────────────────────────────┘
```

**Features:**
- Auto-detect message type
- Color-coded backgrounds
- Bold left border (5px)
- Slide-in animation
- Box shadow for depth

### 3. Progress Box
```
┌─────────────────────────────────────────────┐
│ 📊 Status Progress untuk NIM: venny         │
│                                             │
│  ✅ Langkah 1: Setup OTP - SELESAI         │
│  ✅ Langkah 2: Digital ID - SELESAI        │
│  ⏳ Langkah 3: Sign Data - BELUM           │
│                                             │
│ [Blue gradient background]                  │
└─────────────────────────────────────────────┘
```

**Features:**
- Linear gradient background (light blue)
- Left border accent
- Hover effect on list items (translateX)
- Scale-in animation
- Color-coded status text

### 4. Step Cards
```
┌─────────────────────────────────────────────┐
│  ⓵  Setup OTP Akun                         │
│  ℹ️ Mulai dari sini! Generate QR Code...   │
│                                             │
│  NIM Mahasiswa                              │
│  [Input Field]                              │
│                                             │
│  [🔐 GENERATE QR CODE OTP]                 │
│                                             │
│ [Hover: Lift up + shadow + border color]   │
└─────────────────────────────────────────────┘
```

**Features:**
- Card layout with border radius
- Step number in gradient circle badge
- Helper text with yellow accent
- Hover effects:
  - Translate up (-3px)
  - Border color change to purple
  - Enhanced shadow
- Fade-in-up animation

### 5. Form Elements

#### Input Fields
```
┌─────────────────────────────────────────────┐
│ NIM Mahasiswa                               │
│ ┌─────────────────────────────────────────┐│
│ │ Masukkan NIM kamu                       ││
│ └─────────────────────────────────────────┘│
└─────────────────────────────────────────────┘
```

**States:**
- **Default**: Gray border (2px)
- **Hover**: Darker gray border
- **Focus**: 
  - Purple border
  - Purple glow (box-shadow)
  - Lift up (-2px)

#### Buttons
```
┌─────────────────────────────────────────────┐
│ [  🔐 GENERATE QR CODE OTP  ]              │
│ [Gradient Background + Shadow]              │
└─────────────────────────────────────────────┘
```

**States:**
- **Default**: Gradient + shadow
- **Hover**: 
  - Lift up (-3px)
  - Enhanced shadow
- **Active**: Slight press down (-1px)

### 6. QR Code Container
```
┌─────────────────────────────────────────────┐
│ 📱 Scan QR Code dengan Authenticator App:  │
│                                             │
│         ┌─────────────┐                    │
│         │             │                    │
│         │  QR  CODE   │                    │
│         │             │                    │
│         └─────────────┘                    │
│                                             │
│ Google Authenticator / Microsoft Auth      │
│ [Bounce-in animation]                      │
└─────────────────────────────────────────────┘
```

**Features:**
- White background card
- Centered content
- Bounce animation on appear
- Box shadow for depth
- Rounded corners

## Animations

### 1. fadeIn (Container)
```css
0%   → opacity: 0, translateY(30px)
100% → opacity: 1, translateY(0)
Duration: 0.6s
```

### 2. slideIn (Messages)
```css
0%   → opacity: 0, translateX(-20px)
100% → opacity: 1, translateX(0)
Duration: 0.4s
```

### 3. slideDown (Header)
```css
0%   → opacity: 0, translateY(-20px)
100% → opacity: 1, translateY(0)
Duration: 0.6s-0.8s
```

### 4. fadeInUp (Step Cards)
```css
0%   → opacity: 0, translateY(20px)
100% → opacity: 1, translateY(0)
Duration: 0.5s
```

### 5. scaleIn (Progress Box)
```css
0%   → opacity: 0, scale(0.95)
100% → opacity: 1, scale(1)
Duration: 0.5s
```

### 6. bounceIn (QR Container)
```css
0%   → opacity: 0, scale(0.3)
50%  → opacity: 1, scale(1.05)
100% → scale(1)
Duration: 0.6s
```

## Responsive Breakpoints

### Mobile (max-width: 768px)
- Reduced padding
- Smaller font sizes
- Adjusted button sizes
- Tighter spacing

### Desktop (> 768px)
- Full container width (900px max)
- Larger typography
- Enhanced hover effects
- More spacing

## Accessibility Features

✅ **Semantic HTML**
- Proper heading hierarchy (h1, h3, h4)
- Label-input associations
- Form structure

✅ **Keyboard Navigation**
- Tab order follows visual flow
- Focus states clearly visible

✅ **Visual Clarity**
- High contrast ratios
- Color not sole indicator (icons + text)
- Clear error messages

✅ **Responsive Text**
- Scalable font sizes
- Readable line heights
- Proper spacing

## Browser Compatibility

✅ Chrome/Edge (Chromium)
✅ Firefox
✅ Safari
✅ Opera
✅ Mobile browsers (iOS/Android)

## Performance Optimizations

1. **CSS Only Animations** - No JavaScript for transitions
2. **External Stylesheet** - Cacheable by browser
3. **Minimal HTTP Requests** - Single CSS file
4. **GPU Acceleration** - Transform & opacity animations
5. **No External Dependencies** - Pure CSS, no frameworks

## Design Principles Applied

1. **Consistency** - Uniform spacing, colors, and patterns
2. **Hierarchy** - Clear visual flow from step to step
3. **Feedback** - Immediate visual response to actions
4. **Aesthetics** - Modern, clean, professional
5. **Usability** - Intuitive, self-explanatory interface
