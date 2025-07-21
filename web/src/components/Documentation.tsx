import React, { useState } from 'react';
import {
  Box,
  Typography,
  Paper,
  List,
  ListItem,
  ListItemText,
  Divider,
  Grid,
  Dialog,
  DialogContent,
  IconButton,
} from '@mui/material';
import {
  Code as CodeIcon,
  Security as SecurityIcon,
  Settings as SettingsIcon,
  Folder as FolderIcon,
  Upload as UploadIcon,
  Lock as LockIcon,
  Download as DownloadIcon,
  Close as CloseIcon,
} from '@mui/icons-material';

// 画像のインポート
import fileListScreen from '../assets/images/pages/filelist.png';
import menuScreen from '../assets/images/pages/menu.png';

const Documentation: React.FC = () => {
  const [openImageDialog, setOpenImageDialog] = useState(false);

  const handleImageClick = () => {
    setOpenImageDialog(true);
  };

  const handleCloseDialog = () => {
    setOpenImageDialog(false);
  };

  return (
    <Box sx={{ pt: 4 }}>
      <Typography variant="h1" gutterBottom sx={{ ml: 2 }}>
        ドキュメント
      </Typography>

      <Paper sx={{ p: 3, mb: 3 }}>
        <Typography variant="h2" gutterBottom>
          概要
        </Typography>
        <Typography variant="body1" paragraph>
          BF Secret File Downloaderは、WordPress管理外の非公開フォルダからファイルを安全に配信するプラグインです。
          ファイル一覧画面と設定画面の2つの主要機能で、セキュアなファイル管理を実現します。
        </Typography>
      </Paper>

      <Paper sx={{ p: 3, mb: 3 }}>
        <Typography variant="h2" gutterBottom>
          ファイル一覧画面
        </Typography>
        <Typography variant="body1" paragraph>
          ファイル一覧画面では、設定画面で指定した対象ディレクトリとそのサブディレクトリ内のファイルを管理できます。
          この画面は、WordPressの管理外にある非公開フォルダの内容を安全に閲覧・操作するためのインターフェースです。
        </Typography>

                        {/* 管理画面からのアクセス */}
        <Box sx={{ mb: 4 }}>
          <Typography variant="h3" gutterBottom sx={{ display: 'flex', alignItems: 'center' }}>
            <SettingsIcon sx={{ mr: 1 }} />
            管理画面からのアクセス
          </Typography>
                    <Box sx={{
            display: 'flex',
            flexDirection: { xs: 'column', md: 'row' },
            gap: 3,
            alignItems: { xs: 'flex-start', md: 'flex-start' },
            mb: 2
          }}>
            <Box sx={{ flex: 1, order: { xs: 2, md: 2 } }}>
              <Typography variant="body2" paragraph>
                WordPress管理画面の左サイドバーにある「BF Secret File Downloader」をクリックすると、
                このファイル一覧画面が表示されます。このメニューがプラグインのメイン画面となります。
              </Typography>
            </Box>
                        <Box sx={{
              flexShrink: 0,
              borderRadius: 1,
              overflow: 'hidden',
              border: '1px solid #e0e0e0',
              cursor: 'pointer',
              maxWidth: '140px',
              order: { xs: 1, md: 1 }
            }}>
              <img
                src={menuScreen}
                alt="管理画面のサイドバーメニュー"
                style={{
                  width: '100%',
                  height: 'auto',
                  display: 'block'
                }}
              />
            </Box>
          </Box>
          <Box sx={{ clear: 'both' }}>


          </Box>
        </Box>

        {/* ファイルブラウザ機能 */}
        <Box sx={{ mb: 4 }}>
          <Typography variant="h3" gutterBottom sx={{ display: 'flex', alignItems: 'center' }}>
            <FolderIcon sx={{ mr: 1 }} />
            ファイルブラウザ機能
          </Typography>
          <Typography variant="body2" paragraph>
            指定した対象ディレクトリ内でフォルダ階層をクリックして移動し、ファイルを閲覧・管理できます。
            現在のパスが画面上部に表示され、どの階層にいるかが分かりやすくなっています。
          </Typography>



          <Box sx={{ display: 'grid', gridTemplateColumns: { xs: '1fr', md: '1fr 1fr' }, gap: 3 }}>
                        <Box sx={{ borderRadius: 1, overflow: 'hidden', border: '1px solid #e0e0e0', cursor: 'pointer' }}>
              <img
                src={fileListScreen}
                alt="ファイル一覧画面"
                onClick={handleImageClick}
                style={{
                  width: '100%',
                  height: 'auto',
                  display: 'block'
                }}
              />
            </Box>
            <Box>
              <List dense>
                <ListItem>
                  <ListItemText
                    primary="フォルダをクリックして移動"
                    secondary="フォルダ名をクリックすると、そのフォルダ内に移動します"
                  />
                </ListItem>
                <ListItem>
                  <ListItemText
                    primary="ファイル名でソート"
                    secondary="ファイル名、サイズ、更新日時でソートできます"
                  />
                </ListItem>
                <ListItem>
                  <ListItemText
                    primary="ページネーション"
                    secondary="大量のファイルがある場合はページ分割で表示"
                  />
                </ListItem>
                <ListItem>
                  <ListItemText
                    primary="一括操作"
                    secondary="複数のファイルを選択して一括で操作できます"
                  />
                </ListItem>
                <ListItem>
                  <ListItemText
                    primary="チェックボックス選択"
                    secondary="個別のファイルを選択して操作対象にできます"
                  />
                </ListItem>
              </List>
            </Box>
          </Box>
		        {/* 対象ディレクトリの管理範囲 */}
				<Box sx={{ mb: 4, mt: 4 }}>
          <Paper sx={{ p: 3, bgcolor: 'grey.50', border: '1px solid #e0e0e0' }}>
            <Typography variant="h4" gutterBottom sx={{ display: 'flex', alignItems: 'center' }}>
              <SecurityIcon sx={{ mr: 1 }} />
              対象ディレクトリの管理範囲
            </Typography>
            <Typography variant="body2" paragraph>
              設定画面で指定したディレクトリをルートとして、その配下のすべてのサブディレクトリとファイルを管理対象とします。
              この仕組みにより、WordPressの管理外にあるファイルを安全に配信できます。
            </Typography>
            <Box sx={{ mb: 2, p: 2, bgcolor: 'warning.light', borderRadius: 1 }}>
              <Typography variant="body2" color="warning.contrastText" sx={{ fontWeight: 'bold' }}>
                ⚠️ 重要な制限事項
              </Typography>
              <Typography variant="body2" color="warning.contrastText">
                WordPressの管理下にあるディレクトリ（wp-content、wp-admin、wp-includes等）は指定できません。
                セキュリティ上の理由により、システムディレクトリへのアクセスは自動的にブロックされます。
              </Typography>
            </Box>
            <List dense>
              <ListItem>
                <ListItemText
                  primary="ルートディレクトリ指定"
                  secondary="設定画面で指定したディレクトリが管理の起点となります。このディレクトリを基準として、その配下にあるすべてのファイルとフォルダが管理対象となります。例えば、/var/www/private-files を指定した場合、そのディレクトリ内のすべてのファイルが管理可能になります。"
                />
              </ListItem>
              <ListItem>
                <ListItemText
                  primary="サブディレクトリ管理"
                  secondary="指定したディレクトリ配下のすべてのフォルダとファイルを管理します。深い階層のフォルダも含めて、すべてのサブディレクトリ内のファイルにアクセスできます。フォルダ構造を維持したまま、階層を移動してファイルを閲覧・管理することが可能です。"
                />
              </ListItem>
              <ListItem>
                <ListItemText
                  primary="セキュリティ保護"
                  secondary="設定した対象ディレクトリの範囲外へのアクセスを自動的に拒否します。realpath()を使用してパスの正規化を行い、対象ディレクトリまたはそのサブディレクトリのみアクセスを許可します。また、危険なファイル拡張子（php、phtml、php3、php4、php5、pl、py、jsp、asp、sh、cgi）のアップロードも自動的に拒否してセキュリティを確保します。"
                />
              </ListItem>
            </List>
          </Paper>
          </Box>
        </Box>

        {/* ファイルアップロード */}
        <Box sx={{ mb: 4 }}>
          <Typography variant="h3" gutterBottom sx={{ display: 'flex', alignItems: 'center' }}>
            <UploadIcon sx={{ mr: 1 }} />
            ファイルアップロード
          </Typography>
          <Typography variant="body2" paragraph>
            ドラッグ&ドロップまたはファイル選択でファイルをアップロードできます。
          </Typography>
          <Box sx={{ mb: 2, p: 2, bgcolor: 'grey.100', borderRadius: 1 }}>
            <Typography variant="body2" color="text.secondary">
              ファイルアップロード画面のスクリーンショット
            </Typography>
          </Box>
          <List dense>
            <ListItem>
              <ListItemText
                primary="ドラッグ&ドロップ"
                secondary="ファイルをアップロードエリアにドラッグ&ドロップ"
              />
            </ListItem>
            <ListItem>
              <ListItemText
                primary="ファイル選択ボタン"
                secondary="「ファイルを選択」ボタンをクリックしてファイルを選択"
              />
            </ListItem>
            <ListItem>
              <ListItemText
                primary="サイズ制限"
                secondary="最大100MBまでのファイルをアップロード可能"
              />
            </ListItem>
          </List>
        </Box>

        {/* パスワード保護設定 */}
        <Box sx={{ mb: 4 }}>
          <Typography variant="h3" gutterBottom sx={{ display: 'flex', alignItems: 'center' }}>
            <LockIcon sx={{ mr: 1 }} />
            パスワード保護設定
          </Typography>
          <Typography variant="body2" paragraph>
            各フォルダに個別のパスワードを設定して、アクセス制御を行います。
          </Typography>
          <Box sx={{ mb: 2, p: 2, bgcolor: 'grey.100', borderRadius: 1 }}>
            <Typography variant="body2" color="text.secondary">
              パスワード設定画面のスクリーンショット
            </Typography>
          </Box>
          <List dense>
            <ListItem>
              <ListItemText
                primary="パスワード設定"
                secondary="フォルダ名の横にある鍵アイコンをクリックしてパスワードを設定"
              />
            </ListItem>
            <ListItem>
              <ListItemText
                primary="パスワード削除"
                secondary="設定済みパスワードを削除して保護を解除"
              />
            </ListItem>
            <ListItem>
              <ListItemText
                primary="BASIC認証"
                secondary="アクセス時にブラウザの認証ダイアログが表示されます"
              />
            </ListItem>
          </List>
        </Box>

        {/* ファイルダウンロード */}
        <Box sx={{ mb: 4 }}>
          <Typography variant="h3" gutterBottom sx={{ display: 'flex', alignItems: 'center' }}>
            <DownloadIcon sx={{ mr: 1 }} />
            ファイルダウンロード
          </Typography>
          <Typography variant="body2" paragraph>
            認証済みユーザーがファイルを安全にダウンロードできます。
          </Typography>
          <Box sx={{ mb: 2, p: 2, bgcolor: 'grey.100', borderRadius: 1 }}>
            <Typography variant="body2" color="text.secondary">
              ダウンロード画面のスクリーンショット
            </Typography>
          </Box>
          <List dense>
            <ListItem>
              <ListItemText
                primary="ダウンロードリンク"
                secondary="ファイル名をクリックしてダウンロードを開始"
              />
            </ListItem>
            <ListItem>
              <ListItemText
                primary="認証確認"
                secondary="パスワード保護されたフォルダは認証が必要"
              />
            </ListItem>
            <ListItem>
              <ListItemText
                primary="セキュア配信"
                secondary="WordPress管理外のファイルを安全に配信"
              />
            </ListItem>
          </List>
        </Box>
      </Paper>

      {/* Lightbox Dialog */}
      <Dialog
        open={openImageDialog}
        onClose={handleCloseDialog}
        maxWidth="lg"
        fullWidth
        PaperProps={{
          sx: {
            bgcolor: 'rgba(0, 0, 0, 0.9)',
            boxShadow: 'none',
          }
        }}
      >
        <DialogContent sx={{ p: 0, position: 'relative' }}>
          <IconButton
            onClick={handleCloseDialog}
            sx={{
              position: 'absolute',
              right: 8,
              top: 8,
              color: 'white',
              bgcolor: 'rgba(0, 0, 0, 0.5)',
              '&:hover': {
                bgcolor: 'rgba(0, 0, 0, 0.7)',
              }
            }}
          >
            <CloseIcon />
          </IconButton>
          <Box sx={{ display: 'flex', justifyContent: 'center', alignItems: 'center', minHeight: '80vh' }}>
            <img
              src={fileListScreen}
              alt="ファイル一覧画面（拡大表示）"
              style={{
                maxWidth: '100%',
                maxHeight: '100%',
                objectFit: 'contain'
              }}
            />
          </Box>
        </DialogContent>
      </Dialog>

      <Paper sx={{ p: 3, mt: 3 }}>
        <Box display="flex" alignItems="center" mb={2}>
          <SettingsIcon sx={{ mr: 1 }} />
          <Typography variant="h2">設定画面</Typography>
        </Box>
        <Typography variant="body1" paragraph>
          設定画面では、プラグインの基本設定を行います。
        </Typography>

        {/* 対象ディレクトリ設定 */}
        <Box sx={{ mb: 4 }}>
          <Typography variant="h3" gutterBottom>
            対象ディレクトリ設定
          </Typography>
          <Typography variant="body2" paragraph>
            ファイル管理対象となるディレクトリを指定します。
          </Typography>
          <Box sx={{ mb: 2, p: 2, bgcolor: 'grey.100', borderRadius: 1 }}>
            <Typography variant="body2" color="text.secondary">
              ディレクトリ選択画面のスクリーンショット
            </Typography>
          </Box>
          <List dense>
            <ListItem>
              <ListItemText
                primary="ディレクトリブラウザ"
                secondary="「ディレクトリを選択」ボタンをクリックしてディレクトリを選択"
              />
            </ListItem>
            <ListItem>
              <ListItemText
                primary="パス表示"
                secondary="選択したディレクトリのフルパスが表示されます"
              />
            </ListItem>
            <ListItem>
              <ListItemText
                primary="変更時の注意"
                secondary="ディレクトリを変更すると、既存のパスワード設定はクリアされます"
              />
            </ListItem>
          </List>
        </Box>

        {/* ファイルサイズ制限 */}
        <Box sx={{ mb: 4 }}>
          <Typography variant="h3" gutterBottom>
            ファイルサイズ制限
          </Typography>
          <Typography variant="body2" paragraph>
            アップロード可能なファイルの最大サイズを設定します（1-100MB）。
          </Typography>
          <Box sx={{ mb: 2, p: 2, bgcolor: 'grey.100', borderRadius: 1 }}>
            <Typography variant="body2" color="text.secondary">
              ファイルサイズ設定画面のスクリーンショット
            </Typography>
          </Box>
          <List dense>
            <ListItem>
              <ListItemText
                primary="数値入力"
                secondary="1から100の間で数値を入力（単位：MB）"
              />
            </ListItem>
            <ListItem>
              <ListItemText
                primary="デフォルト値"
                secondary="初期値は10MBに設定されています"
              />
            </ListItem>
            <ListItem>
              <ListItemText
                primary="サーバー制限"
                secondary="サーバーのPHP設定（upload_max_filesize）も確認してください"
              />
            </ListItem>
          </List>
        </Box>

        {/* セキュリティ設定 */}
        <Box sx={{ mb: 4 }}>
          <Typography variant="h3" gutterBottom>
            セキュリティ設定
          </Typography>
          <Typography variant="body2" paragraph>
            危険なファイル拡張子のアップロードを自動的に拒否します。
          </Typography>
          <Box sx={{ mb: 2, p: 2, bgcolor: 'grey.100', borderRadius: 1 }}>
            <Typography variant="body2" color="text.secondary">
              セキュリティ設定画面のスクリーンショット
            </Typography>
          </Box>
          <List dense>
            <ListItem>
              <ListItemText
                primary="拒否ファイル拡張子"
                secondary="PHP、CGI、PL、PY、JSP、ASP、SH等の実行可能ファイル"
              />
            </ListItem>
            <ListItem>
              <ListItemText
                primary="自動チェック"
                secondary="アップロード時に自動的にファイル拡張子をチェック"
              />
            </ListItem>
            <ListItem>
              <ListItemText
                primary="エラーメッセージ"
                secondary="拒否されたファイルはエラーメッセージで通知"
              />
            </ListItem>
          </List>
        </Box>
      </Paper>

      <Paper sx={{ p: 3, mt: 3 }}>
        <Typography variant="h6" gutterBottom>
          セキュリティ機能
        </Typography>
        <Divider sx={{ mb: 2 }} />
        <Typography variant="body1" paragraph>
          このプラグインは以下のセキュリティ機能を提供します：
        </Typography>
        <List>
          <ListItem>
            <ListItemText
              primary="BASIC認証"
              secondary="ディレクトリ単位でのパスワード保護"
            />
          </ListItem>
          <ListItem>
            <ListItemText
              primary="ファイルタイプ制限"
              secondary="PHP、CGI等の危険なファイル拡張子の自動拒否"
            />
          </ListItem>
          <ListItem>
            <ListItemText
              primary="アクセス制御"
              secondary="システムディレクトリへのアクセスブロック"
            />
          </ListItem>
          <ListItem>
            <ListItemText
              primary="権限チェック"
              secondary="AJAX通信時のnonce検証と権限チェック"
            />
          </ListItem>
        </List>
      </Paper>
    </Box>
  );
};

export default Documentation;