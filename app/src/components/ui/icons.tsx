import React from 'react';
import Svg, { Path, Circle, Rect, Line, Polyline, SvgProps } from 'react-native-svg';

export interface IconProps extends SvgProps {
  size?: number;
  color?: string;
  strokeWidth?: number;
}

const defaultProps = {
  size: 20,
  color: '#000',
  strokeWidth: 2,
};

function createIcon(renderPaths: (props: IconProps) => React.ReactNode) {
  return function Icon({ size = 20, color = 'currentColor', strokeWidth = 2, style, ...rest }: IconProps) {
    return (
      <Svg
        width={size}
        height={size}
        viewBox="0 0 24 24"
        fill="none"
        stroke={color}
        strokeWidth={strokeWidth}
        strokeLinecap="round"
        strokeLinejoin="round"
        style={style}
        {...rest}>
        {renderPaths({ size, color, strokeWidth, ...rest })}
      </Svg>
    );
  };
}

export const Newspaper = createIcon(() => (
  <>
    <Path d="M4 22h16a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2H8a2 2 0 0 0-2 2v16a2 2 0 0 1-2 2Zm0 0a2 2 0 0 1-2-2v-9c0-1.1.9-2 2-2h2" />
    <Path d="M18 14h-8" />
    <Path d="M15 18h-5" />
    <Path d="M10 6h8v4h-8V6Z" />
  </>
));

export const Globe = createIcon(() => (
  <>
    <Circle cx="12" cy="12" r="10" />
    <Path d="M12 2a14.5 14.5 0 0 0 0 20 14.5 14.5 0 0 0 0-20" />
    <Path d="M2 12h20" />
  </>
));

export const Server = createIcon(() => (
  <>
    <Rect width="20" height="8" x="2" y="2" rx="2" ry="2" />
    <Rect width="20" height="8" x="2" y="14" rx="2" ry="2" />
    <Line x1="6" x2="6.01" y1="6" y2="6" />
    <Line x1="6" x2="6.01" y1="18" y2="18" />
  </>
));

export const ShieldCheck = createIcon(() => (
  <>
    <Path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z" />
    <Path d="m9 12 2 2 4-4" />
  </>
));

export const User = createIcon(() => (
  <>
    <Path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2" />
    <Circle cx="12" cy="7" r="4" />
  </>
));

export const Lock = createIcon(() => (
  <>
    <Rect width="18" height="11" x="3" y="11" rx="2" ry="2" />
    <Path d="M7 11V7a5 5 0 0 1 10 0v4" />
  </>
));

export const Eye = createIcon(() => (
  <>
    <Path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z" />
    <Circle cx="12" cy="12" r="3" />
  </>
));

export const EyeOff = createIcon(() => (
  <>
    <Path d="m2 2 20 20" />
    <Path d="M9.88 9.88a3 3 0 1 0 4.24 4.24" />
    <Path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68" />
    <Path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61" />
  </>
));

export const ArrowRight = createIcon(() => (
  <>
    <Path d="M5 12h14" />
    <Path d="m12 5 7 7-7 7" />
  </>
));

export const RefreshCw = createIcon(() => (
  <>
    <Path d="M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8" />
    <Path d="M21 3v5h-5" />
    <Path d="M21 12a9 9 0 0 1-9 9 9.75 9.75 0 0 1-6.74-2.74L3 16" />
    <Path d="M8 16H3v5" />
  </>
));

export const LogOut = createIcon(() => (
  <>
    <Path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
    <Polyline points="16 17 21 12 16 7" />
    <Line x1="21" x2="9" y1="12" y2="12" />
  </>
));

export const CheckCircle2 = createIcon(() => (
  <>
    <Circle cx="12" cy="12" r="10" />
    <Path d="m9 12 2 2 4-4" />
  </>
));

export const AlertCircle = createIcon(() => (
  <>
    <Circle cx="12" cy="12" r="10" />
    <Line x1="12" x2="12" y1="8" y2="12" />
    <Line x1="12" x2="12.01" y1="16" y2="16" />
  </>
));

export const Plus = createIcon(() => (
  <>
    <Path d="M5 12h14" />
    <Path d="M12 5v14" />
  </>
));

export const FileText = createIcon(() => (
  <>
    <Path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z" />
    <Path d="M14 2v4a2 2 0 0 0 2 2h4" />
    <Path d="M10 9H8" />
    <Path d="M16 13H8" />
    <Path d="M16 17H8" />
  </>
));

export const Layers = createIcon(() => (
  <>
    <Path d="m12.83 2.18a2 2 0 0 0-1.66 0L2.6 6.08a1 1 0 0 0 0 1.83l8.58 3.91a2 2 0 0 0 1.66 0l8.58-3.9a1 1 0 0 0 0-1.83Z" />
    <Path d="m22 17.65-9.17 4.16a2 2 0 0 1-1.66 0L2 17.65" />
    <Path d="m22 12.65-9.17 4.16a2 2 0 0 1-1.66 0L2 12.65" />
  </>
));

export const Radio = createIcon(() => (
  <>
    <Circle cx="12" cy="12" r="2" />
    <Path d="M16.24 7.76a6 6 0 0 1 0 8.49" />
    <Path d="M7.76 16.24a6 6 0 0 1 0-8.49" />
    <Path d="M19.07 4.93a10 10 0 0 1 0 14.14" />
    <Path d="M4.93 19.07a10 10 0 0 1 0-14.14" />
  </>
));

export const Zap = createIcon(() => (
  <Path d="M4 14a1 1 0 0 1-.78-1.63l9.9-10.2a.5.5 0 0 1 .86.46l-1.92 6.02A1 1 0 0 0 13 10h7a1 1 0 0 1 .78 1.63l-9.9 10.2a.5.5 0 0 1-.86-.46l1.92-6.02A1 1 0 0 0 11 14z" />
));

export const FileEdit = createIcon(() => (
  <>
    <Path d="M4 13.5V4a2 2 0 0 1 2-2h8.5L20 7.5V20a2 2 0 0 1-2 2h-5.5" />
    <Polyline points="14 2 14 8 20 8" />
    <Path d="M10.42 12.61a2.1 2.1 0 1 1 2.97 2.97L7.95 21 4 22l1-3.95 5.42-5.44Z" />
  </>
));

export const MessageSquare = createIcon(() => (
  <Path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
));

export const Pencil = createIcon(() => (
  <Path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z" />
));

export const Clock = createIcon(() => (
  <>
    <Circle cx="12" cy="12" r="10" />
    <Polyline points="12 6 12 12 16 14" />
  </>
));

export const TrendingUp = createIcon(() => (
  <>
    <Polyline points="22 7 13.5 15.5 8.5 10.5 2 17" />
    <Polyline points="16 7 22 7 22 13" />
  </>
));

export const Search = createIcon(() => (
  <>
    <Circle cx="11" cy="11" r="8" />
    <Path d="m21 21-4.3-4.3" />
  </>
));

export const Trash2 = createIcon(() => (
  <>
    <Path d="M3 6h18" />
    <Path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6" />
    <Path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2" />
    <Line x1="10" x2="10" y1="11" y2="17" />
    <Line x1="14" x2="14" y1="11" y2="17" />
  </>
));

export const Star = createIcon(({ fill }: any) => (
  <Path
    fill={fill || 'none'}
    d="M11.525 2.295a.53.53 0 0 1 .95 0l2.31 4.679a2.123 2.123 0 0 0 1.595 1.16l5.167.751c.467.068.654.643.316.972l-3.738 3.644a2.127 2.127 0 0 0-.611 1.882l.882 5.146c.08.466-.41.822-.828.602l-4.62-2.43a2.12 2.12 0 0 0-1.972 0l-4.62 2.43c-.418.22-.908-.136-.828-.602l.882-5.146a2.127 2.127 0 0 0-.61-1.882L1.696 9.857c-.338-.329-.15-.904.317-.972l5.166-.75a2.123 2.123 0 0 0 1.596-1.161z"
  />
));

export const ChevronLeft = createIcon(() => (
  <Path d="m15 18-6-6 6-6" />
));

export const ChevronRight = createIcon(() => (
  <Path d="m9 18 6-6-6-6" />
));

export const Inbox = createIcon(() => (
  <>
    <Polyline points="22 12 16 12 14 15 10 15 8 12 2 12" />
    <Path d="M5.45 5.11 2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z" />
  </>
));

export const X = createIcon(() => (
  <>
    <Path d="M18 6 6 18" />
    <Path d="m6 6 12 12" />
  </>
));

export const Check = createIcon(() => (
  <Polyline points="20 6 9 17 4 12" />
));

export const Save = createIcon(() => (
  <>
    <Path d="M15.2 3a2 2 0 0 1 1.4.6l3.8 3.8a2 2 0 0 1 .6 1.4V19a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2z" />
    <Path d="M17 21v-7a1 1 0 0 0-1-1H8a1 1 0 0 0-1 1v7" />
    <Path d="M7 3v4a1 1 0 0 0 1 1h7" />
  </>
));

export const Send = createIcon(() => (
  <>
    <Path d="m22 2-7 20-4-9-9-4Z" />
    <Path d="M22 2 11 13" />
  </>
));

export const ImageIcon = createIcon(() => (
  <>
    <Rect width="18" height="18" x="3" y="3" rx="2" ry="2" />
    <Circle cx="9" cy="9" r="2" />
    <Path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21" />
  </>
));

export const Video = createIcon(() => (
  <>
    <Path d="m22 8-6 4 6 4V8Z" />
    <Rect width="14" height="12" x="2" y="6" rx="2" ry="2" />
  </>
));

export const Mail = createIcon(() => (
  <>
    <Rect width="20" height="16" x="2" y="4" rx="2" />
    <Path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7" />
  </>
));

export const Phone = createIcon(() => (
  <Path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z" />
));

export const Settings = createIcon(() => (
  <>
    <Path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z" />
    <Circle cx="12" cy="12" r="3" />
  </>
));

export const LayoutDashboard = createIcon(() => (
  <>
    <Rect width="7" height="9" x="3" y="3" rx="1" />
    <Rect width="7" height="5" x="14" y="3" rx="1" />
    <Rect width="7" height="9" x="14" y="12" rx="1" />
    <Rect width="7" height="5" x="3" y="16" rx="1" />
  </>
));

export const ExternalLink = createIcon(() => (
  <>
    <Path d="M15 3h6v6" />
    <Path d="M10 14 21 3" />
    <Path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6" />
  </>
));

export const Laptop = createIcon(() => (
  <>
    <Path d="M20 16V7a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v9m16 0H4m16 0 1.28 2.55a1 1 0 0 1-.9 1.45H3.62a1 1 0 0 1-.9-1.45L4 16" />
  </>
));

export const Info = createIcon(() => (
  <>
    <Circle cx="12" cy="12" r="10" />
    <Path d="M12 16v-4" />
    <Path d="M12 8h.01" />
  </>
));

export const ArrowLeft = createIcon(() => (
  <>
    <Path d="m12 19-7-7 7-7" />
    <Path d="M19 12H5" />
  </>
));

export const BarChart3 = createIcon(() => (
  <>
    <Path d="M3 3v18h18" />
    <Path d="M18 17V9" />
    <Path d="M13 17V5" />
    <Path d="M8 17v-3" />
  </>
));

export const PieChart = createIcon(() => (
  <>
    <Path d="M21.21 15.89A10 10 0 1 1 8 2.83" />
    <Path d="M22 12A10 10 0 0 0 12 2v10z" />
  </>
));

export const Calendar = createIcon(() => (
  <>
    <Rect width="18" height="18" x="3" y="4" rx="2" ry="2" />
    <Line x1="16" x2="16" y1="2" y2="6" />
    <Line x1="8" x2="8" y1="2" y2="6" />
    <Line x1="3" x2="21" y1="10" y2="10" />
  </>
));

export const Users = createIcon(() => (
  <>
    <Path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
    <Circle cx="9" cy="7" r="4" />
    <Path d="M22 21v-2a4 4 0 0 0-3-3.87" />
    <Path d="M16 3.13a4 4 0 0 1 0 7.75" />
  </>
));

export const Upload = createIcon(() => (
  <>
    <Path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
    <Polyline points="17 8 12 3 7 8" />
    <Line x1="12" x2="12" y1="3" y2="15" />
  </>
));

export const Copy = createIcon(() => (
  <>
    <Rect width="14" height="14" x="8" y="8" rx="2" ry="2" />
    <Path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2" />
  </>
));

export const Bold = createIcon(() => (
  <Path d="M6 12h9a4 4 0 0 1 0 8H6v-8zm0-8h8a3.5 3.5 0 0 1 0 7H6V4z" />
));

export const Italic = createIcon(() => (
  <>
    <Line x1="19" x2="10" y1="4" y2="4" />
    <Line x1="14" x2="5" y1="20" y2="20" />
    <Line x1="15" x2="9" y1="4" y2="20" />
  </>
));

export const Heading = createIcon(() => (
  <>
    <Path d="M6 12h12" />
    <Path d="M6 20V4" />
    <Path d="M18 20V4" />
  </>
));

export const List = createIcon(() => (
  <>
    <Line x1="8" x2="21" y1="6" y2="6" />
    <Line x1="8" x2="21" y1="12" y2="12" />
    <Line x1="8" x2="21" y1="18" y2="18" />
    <Line x1="3" x2="3.01" y1="6" y2="6" />
    <Line x1="3" x2="3.01" y1="12" y2="12" />
    <Line x1="3" x2="3.01" y1="18" y2="18" />
  </>
));

export const ListOrdered = createIcon(() => (
  <>
    <Line x1="10" x2="21" y1="6" y2="6" />
    <Line x1="10" x2="21" y1="12" y2="12" />
    <Line x1="10" x2="21" y1="18" y2="18" />
    <Path d="M4 6h1v4" />
    <Path d="M4 10h2" />
    <Path d="M6 18H4c0-1 2-2 2-3s-1-1.5-2-1" />
  </>
));

export const Quote = createIcon(() => (
  <>
    <Path d="M3 21c3 0 7-1 7-8V5c0-1.1-.9-2-2-2H4c-1.1 0-2 .9-2 2v6c0 1.1.9 2 2 2 0 4-1 6-1 8" />
    <Path d="M15 21c3 0 7-1 7-8V5c0-1.1-.9-2-2-2h-4c-1.1 0-2 .9-2 2v6c0 1.1.9 2 2 2 0 4-1 6-1 8" />
  </>
));

export const LinkIcon = createIcon(() => (
  <>
    <Path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71" />
    <Path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71" />
  </>
));

export const Maximize2 = createIcon(() => (
  <>
    <Polyline points="15 3 21 3 21 9" />
    <Polyline points="9 21 3 21 3 15" />
    <Line x1="21" x2="14" y1="3" y2="10" />
    <Line x1="3" x2="10" y1="21" y2="14" />
  </>
));

export const Underline = createIcon(() => (
  <>
    <Path d="M6 3v7a6 6 0 0 0 12 0V3" />
    <Line x1="4" x2="20" y1="21" y2="21" />
  </>
));

export const Strikethrough = createIcon(() => (
  <>
    <Path d="M16 4H9a3 3 0 0 0-2.83 4" />
    <Path d="M14 12a4 4 0 0 1 0 8H6" />
    <Line x1="4" x2="20" y1="12" y2="12" />
  </>
));

export const AlignLeft = createIcon(() => (
  <>
    <Line x1="21" x2="3" y1="6" y2="6" />
    <Line x1="15" x2="3" y1="12" y2="12" />
    <Line x1="17" x2="3" y1="18" y2="18" />
  </>
));

export const AlignCenter = createIcon(() => (
  <>
    <Line x1="21" x2="3" y1="6" y2="6" />
    <Line x1="19" x2="5" y1="12" y2="12" />
    <Line x1="21" x2="3" y1="18" y2="18" />
  </>
));

export const AlignRight = createIcon(() => (
  <>
    <Line x1="21" x2="3" y1="6" y2="6" />
    <Line x1="21" x2="9" y1="12" y2="12" />
    <Line x1="21" x2="7" y1="18" y2="18" />
  </>
));

export const AlignJustify = createIcon(() => (
  <>
    <Line x1="21" x2="3" y1="6" y2="6" />
    <Line x1="21" x2="3" y1="12" y2="12" />
    <Line x1="21" x2="3" y1="18" y2="18" />
  </>
));

export const Code = createIcon(() => (
  <>
    <Polyline points="16 18 22 12 16 6" />
    <Polyline points="8 6 2 12 8 18" />
  </>
));

export const Subscript = createIcon(() => (
  <>
    <Path d="m4 5 8 8" />
    <Path d="m12 5-8 8" />
    <Path d="M20 19h-4c0-1.5.44-2 1.5-2.5.8-.4 1.5-.9 1.5-1.5s-.5-1-1-1-1 .5-1 1" />
  </>
));

export const Superscript = createIcon(() => (
  <>
    <Path d="m4 19 8-8" />
    <Path d="m12 19-8-8" />
    <Path d="M20 9h-4c0-1.5.44-2 1.5-2.5.8-.4 1.5-.9 1.5-1.5s-.5-1-1-1-1 .5-1 1" />
  </>
));

export const Indent = createIcon(() => (
  <>
    <Polyline points="3 8 7 12 3 16" />
    <Line x1="21" x2="11" y1="12" y2="12" />
    <Line x1="21" x2="11" y1="6" y2="6" />
    <Line x1="21" x2="11" y1="18" y2="18" />
  </>
));

export const Outdent = createIcon(() => (
  <>
    <Polyline points="7 8 3 12 7 16" />
    <Line x1="21" x2="11" y1="12" y2="12" />
    <Line x1="21" x2="11" y1="6" y2="6" />
    <Line x1="21" x2="11" y1="18" y2="18" />
  </>
));

export const Palette = createIcon(() => (
  <>
    <Circle cx="13.5" cy="6.5" r=".5" fill="currentColor" />
    <Circle cx="17.5" cy="10.5" r=".5" fill="currentColor" />
    <Circle cx="8.5" cy="7.5" r=".5" fill="currentColor" />
    <Circle cx="6.5" cy="12.5" r=".5" fill="currentColor" />
    <Path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10c.926 0 1.648-.746 1.648-1.688 0-.437-.18-.835-.437-1.125-.29-.289-.438-.652-.438-1.125a1.64 1.64 0 0 1 1.668-1.668h1.996c3.051 0 5.563-2.512 5.563-5.563C22 6.5 17.5 2 12 2Z" />
  </>
));

export const Highlighter = createIcon(() => (
  <>
    <Path d="m9 11-6 6v3h3l6-6" />
    <Path d="m22 12-4.6 4.6a2 2 0 0 1-2.8 0l-5.2-5.2a2 2 0 0 1 0-2.8L14 4" />
  </>
));

export const Eraser = createIcon(() => (
  <>
    <Path d="m7 21-4.3-4.3c-1-1-1-2.5 0-3.4l9.6-9.6c1-1 2.5-1 3.4 0l5.6 5.6c1 1 1 2.5 0 3.4L13 21" />
    <Path d="M22 21H7" />
    <Path d="m5 11 9 9" />
  </>
));

export const Camera = createIcon(() => (
  <>
    <Path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3l-2.5-3z" />
    <Circle cx="12" cy="13" r="3" />
  </>
));

export const LayoutGrid = createIcon(() => (
  <>
    <Rect width="7" height="7" x="3" y="3" rx="1" />
    <Rect width="7" height="7" x="14" y="3" rx="1" />
    <Rect width="7" height="7" x="14" y="14" rx="1" />
    <Rect width="7" height="7" x="3" y="14" rx="1" />
  </>
));




