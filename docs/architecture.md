# Architecture Design - VOIC

## 1. High-Level Architecture
Aplikasi menggunakan arsitektur Monolithic modern dengan Laravel 12.

[ Browser / Client ] 
       │ (Webcam & Mic Stream)
       ▼
[ Laravel 12 (Web & API Routes) ] 
       │ 
       ├──► [ MySQL Database ] (Simpan User, Sesi, & Skor)
       └──► [ Client-Side AI (Face-API.js / Web Speech API) ] (Analisis Ekspresi & Suara di Browser)