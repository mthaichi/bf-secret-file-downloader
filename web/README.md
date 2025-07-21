# BF Secret File Downloader - ウェブサイト

このプロジェクトは、BF Secret File Downloaderプラグインの公式ウェブサイトです。Material Dashboard Reactを使用して構築されています。

## 機能

- **ダッシュボード**: プラグインの統計情報とアクティビティを表示
- **ファイル管理**: アップロードされたファイルの一覧と管理
- **設定**: プラグインの各種設定項目
- **ドキュメント**: インストール方法、セキュリティ機能、設定項目の説明

## 技術スタック

- React 18
- TypeScript
- Material-UI (MUI)
- React Router DOM
- Material Dashboard React テーマ

## 開発環境のセットアップ

### 前提条件

- Node.js 16以上
- npm または yarn

### インストール

```bash
# 依存関係のインストール
npm install

# 開発サーバーの起動
npm start
```

### ビルド

```bash
# 本番用ビルド
npm run build
```

## プロジェクト構造

```
src/
├── components/          # ページコンポーネント
│   ├── Dashboard.tsx
│   ├── FileManagement.tsx
│   ├── Settings.tsx
│   ├── Documentation.tsx
│   ├── Sidebar.tsx
│   └── Navbar.tsx
├── layouts/            # レイアウトコンポーネント
│   └── DashboardLayout.tsx
├── assets/             # アセットとテーマ
│   └── theme/
└── App.tsx            # メインアプリケーション
```

## ページ構成

1. **ダッシュボード** (`/`)
   - プラグインの統計情報
   - 最近のアクティビティ
   - システム情報

2. **ファイル管理** (`/files`)
   - アップロードされたファイルの一覧
   - ファイルの詳細情報
   - ダウンロード、編集、削除機能

3. **設定** (`/settings`)
   - 基本設定
   - セキュリティ設定
   - 通知設定

4. **ドキュメント** (`/docs`)
   - インストール方法
   - セキュリティ機能の説明
   - 設定項目の詳細

## カスタマイズ

### テーマの変更

`src/assets/theme/index.ts` を編集して、カラーパレットやコンポーネントのスタイルを変更できます。

### 新しいページの追加

1. `src/components/` に新しいコンポーネントを作成
2. `src/App.tsx` にルートを追加
3. `src/components/Sidebar.tsx` にメニュー項目を追加

## デプロイ

このウェブサイトは静的サイトとしてデプロイできます：

```bash
# ビルド
npm run build

# build/ ディレクトリの内容をWebサーバーにアップロード
```

## ライセンス

このプロジェクトはMITライセンスの下で公開されています。
